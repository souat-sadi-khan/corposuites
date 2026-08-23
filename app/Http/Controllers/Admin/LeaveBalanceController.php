<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveBalanceGroupRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\LeaveAccrualService;
use App\Services\LeaveBalanceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveBalanceController extends Controller
{
    use ActivityLogger;

    protected $leaveBalanceService;
    protected $leaveAccrualService;

    public function __construct(LeaveBalanceService $leaveBalanceService, LeaveAccrualService $leaveAccrualService)
    {
        $this->leaveBalanceService = $leaveBalanceService;
        $this->leaveAccrualService = $leaveAccrualService;
    }

    /**
     * Display a listing of the resource — ONE ROW PER EMPLOYEE PER YEAR
     * (not one row per employee+leave-type+year the way the underlying
     * `leave_balances` table itself is still shaped). Every leave type an
     * employee has a balance for in that year is rolled up into one
     * summary row here; the "Manage" action opens the full per-type
     * breakdown (see manageForm()/manage() below).
     *
     * Deliberately NOT Yajra here — this is a GROUPED, in-memory rollup of
     * an already-fetched (batch, not per-row) query, not a straight
     * Eloquent/Collection passthrough Yajra's engines are built for — so
     * this hand-builds the exact same draw/recordsTotal/recordsFiltered/
     * data JSON contract the DataTables.net client already expects from
     * every other server-side table in this project, just without routing
     * through Yajra for this one grouped screen.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LeaveBalance::query()->with(['employee.department', 'employee.designation', 'leaveType']);

            if ($request->status) {
                $query->whereIn('status', explode(',', $request->status));
            }
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('year')) {
                $query->where('year', $request->input('year'));
            }

            $groups = $query->get()
                ->groupBy(fn ($row) => $row->employee_id . '|' . $row->year)
                ->map(function ($items, $key) {
                    [$employeeId, $year] = explode('|', $key);
                    $employee = $items->first()->employee;

                    return (object) [
                        'key' => $key,
                        'employee_id' => (int) $employeeId,
                        'year' => (int) $year,
                        'employee' => $employee,
                        'types_count' => $items->count(),
                        'active_count' => $items->where('status', true)->count(),
                        'total_allocated' => (float) $items->sum('allocated_days'),
                        'total_used' => (float) $items->sum('used_days'),
                        'total_carried' => (float) $items->sum('carried_days'),
                        'total_remaining' => (float) $items->sum(fn ($b) => $b->remaining_days),
                    ];
                })
                ->values();

            if ($request->filled('search')) {
                $search = mb_strtolower($request->input('search'));
                $groups = $groups->filter(function ($row) use ($search) {
                    $name = mb_strtolower($row->employee->full_name ?? '');
                    $code = mb_strtolower($row->employee->employee_code ?? '');
                    return str_contains($name, $search)
                        || str_contains($code, $search)
                        || str_contains((string) $row->year, $search);
                })->values();
            }

            $groups = $groups->sortByDesc(fn ($row) => $row->year . '-' . ($row->employee->full_name ?? ''))->values();

            $recordsTotal = $groups->count();
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 9);
            $length = $length > 0 ? $length : $recordsTotal;

            $page = $groups->slice($start, $length)->values();

            $data = $page->map(function ($row) {
                $avatar = Images::show($row->employee->photo ?? null);

                return [
                    'id' => $row->key,
                    'employee_name' => '
                        <div class="d-flex align-items-center">
                            <div class="mr-2 employee-avatar">' . $avatar . '</div>
                            <div>
                                <b class="tl-name-txt">' . e($row->employee->full_name ?? '—') . '</b>
                                <br>
                                <small>' . e($row->employee->employee_code ?? '') . '</small>
                            </div>
                        </div>',
                    'year_label' => '<b>' . $row->year . '</b>',
                    'types_summary' => $row->types_count . ' leave type' . ($row->types_count === 1 ? '' : 's')
                        . '<br><small class="text-muted">' . $row->active_count . ' active</small>',
                    'balance' => number_format($row->total_allocated, 2) . ' allocated / ' . number_format($row->total_used, 2) . ' used'
                        . '<br><small>' . number_format($row->total_remaining, 2) . ' remaining' . ($row->total_carried > 0 ? ' · ' . number_format($row->total_carried, 2) . ' carried' : '') . '</small>',
                    'action' => view('admin.leave-balances.action', [
                        'employeeId' => $row->employee_id,
                        'year' => $row->year,
                        'typesCount' => $row->types_count,
                    ])->render(),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsTotal,
                'data' => $data,
            ]);
        }

        return view('admin.leave-balances.index');
    }

    /**
     * Manage form (create) — pick an Employee + Year, then add one line per
     * leave type. Blank slate: no existing group yet.
     */
    public function create()
    {
        $employees = Employee::active()->orderBy('first_name')->get();
        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        return view('admin.leave-balances.manage', [
            'employee' => null,
            'year' => now()->year,
            'existingItems' => [],
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Manage form (edit) — an EXISTING employee+year group, pre-populated
     * with every leave type it currently has a balance row for.
     */
    public function edit(Employee $employee, int $year)
    {
        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        $existingItems = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $year)
            ->with('leaveType')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'leave_type_id' => $b->leave_type_id,
                'leave_type_name' => $b->leaveType->name ?? ('#' . $b->leave_type_id),
                'allocated_days' => $b->allocated_days,
                'used_days' => $b->used_days,
                'carried_days' => $b->carried_days,
                'carry_expires_on' => optional($b->carry_expires_on)->toDateString(),
                'status' => (bool) $b->status,
                'is_encashable' => (bool) ($b->leaveType->is_encashable ?? false),
                'remaining_days' => $b->remaining_days,
            ])
            ->values();

        return view('admin.leave-balances.manage', [
            'employee' => $employee,
            'year' => $year,
            'existingItems' => $existingItems,
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Store — a brand new employee+year group.
     */
    public function store(LeaveBalanceGroupRequest $request)
    {
        DB::beginTransaction();

        try {
            $employeeId = (int) $request->input('employee_id');
            $year = (int) $request->input('year');

            $saved = $this->leaveBalanceService->saveGroup($employeeId, $year, $request->input('items', []));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-balances',
                'action' => 'create',
                'model' => 'LeaveBalance',
                'model_id' => null,
                'description' => "Leave balance record created for employee #{$employeeId}, year {$year} ({$saved->count()} leave type(s)).",
                'new_data' => $saved->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.leave-balances.index'),
                'message' => 'Leave balance record saved successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update — an existing employee+year group (upsert-and-prune, see
     * LeaveBalanceService::saveGroup()).
     */
    public function update(LeaveBalanceGroupRequest $request, Employee $employee, int $year)
    {
        DB::beginTransaction();

        try {
            $oldData = LeaveBalance::where('employee_id', $employee->id)->where('year', $year)->get()->toArray();

            $saved = $this->leaveBalanceService->saveGroup($employee->id, $year, $request->input('items', []));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-balances',
                'action' => 'update',
                'model' => 'LeaveBalance',
                'model_id' => null,
                'description' => "Leave balance record updated for employee #{$employee->id}, year {$year} ({$saved->count()} leave type(s)).",
                'new_data' => $saved->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.leave-balances.index'),
                'message' => 'Leave balance record updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an ENTIRE employee+year group (every leave type under it) —
     * the "Delete" action on the grouped index row.
     */
    public function destroyGroup(Employee $employee, int $year)
    {
        DB::beginTransaction();

        try {
            $oldData = LeaveBalance::where('employee_id', $employee->id)->where('year', $year)->get()->toArray();
            $count = $this->leaveBalanceService->deleteGroup($employee->id, $year);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-balances',
                'action' => 'delete',
                'model' => 'LeaveBalance',
                'model_id' => null,
                'description' => "Leave balance record deleted for employee #{$employee->id}, year {$year} ({$count} leave type(s)).",
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Deleted {$count} leave type balance(s) for {$year}.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Backfill (auto-allocate) leave balances for one employee or all active
     * employees for a given year, based on each active leave type's accrual policy.
     * Complements the automatic allocation that fires when a new employee is created.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'employee_id' => 'nullable|integer|exists:employees,id',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $year = (int) ($request->input('year') ?: now()->year);
        $created = 0;

        DB::beginTransaction();

        try {
            $employees = $request->filled('employee_id')
                ? Employee::where('id', $request->employee_id)->get()
                : Employee::where('status', 1)->get();

            foreach ($employees as $employee) {
                $created += $this->leaveAccrualService->allocateForEmployee($employee, $year);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-balances',
                'action' => 'generate',
                'model' => 'LeaveBalance',
                'model_id' => null,
                'description' => "Auto-generated {$created} leave balance(s) for year {$year}.",
                'new_data' => ['year' => $year, 'created' => $created],
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Generated {$created} leave balance(s) for {$year}.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Encash remaining balance for an encashable leave type (Phase C4).
     */
    public function encash(Request $request, LeaveBalance $leaveBalance)
    {
        $request->validate([
            'days' => 'nullable|numeric|min:0.5',
            'remarks' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $encashment = $this->leaveAccrualService->encash(
                $leaveBalance->employee,
                $leaveBalance->leaveType,
                $leaveBalance->year,
                $request->filled('days') ? (float) $request->input('days') : null,
                $request->input('remarks')
            );

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-balances',
                'action' => 'encash',
                'model' => 'LeaveEncashment',
                'model_id' => $encashment->id,
                'description' => "Encashed {$encashment->days} day(s) for employee #{$leaveBalance->employee_id}.",
                'new_data' => $encashment->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Encashed {$encashment->days} day(s) successfully.",
            ]);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update status (AJAX switch toggle) — per single leave-type line,
     * used from inside the Manage form.
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = LeaveBalance::find($id);
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.'
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.'
        ]);
    }
}
