<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveBalanceRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeaveBalanceController extends Controller
{
    use ActivityLogger;

    protected $leaveBalanceService;

    public function __construct(LeaveBalanceService $leaveBalanceService)
    {
        $this->leaveBalanceService = $leaveBalanceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LeaveBalance::query()->with(['employee', 'leaveType']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('employee', function ($eq) use ($search) {
                        $eq->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                           ->orWhere('employee_code', 'like', "%{$search}%");
                    })->orWhereHas('leaveType', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%");
                    });
                });
            }

            $query->orderBy('year', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.leave-balances.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('leave_type_name', function ($row) {
                    return ($row->leaveType->name ?? '-') . '<br><small>' . $row->year . '</small>';
                })
                ->addColumn('balance', function ($row) {
                    return number_format($row->allocated_days, 2) . ' allocated / ' . number_format($row->used_days, 2) . ' used<br><small>' . number_format($row->remaining_days, 2) . ' remaining</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.leave-balances.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'leave_type_name', 'balance', 'action'])
                ->make(true);
        }

        return view('admin.leave-balances.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();
        $leaveTypes = LeaveType::active()->get();

        return view('admin.leave-balances.create', compact('employees', 'leaveTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeaveBalanceRequest $request)
    {
        DB::beginTransaction();

        try {
            $leaveBalance = $this->leaveBalanceService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-balances',
                'action' => 'create',
                'model' => 'LeaveBalance',
                'model_id' => $leaveBalance->id,
                'description' => 'Leave balance created for employee #' . $leaveBalance->employee_id,
                'new_data' => $leaveBalance->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave balance created successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveBalance $leaveBalance)
    {
        $employees = Employee::active()->get();
        $leaveTypes = LeaveType::active()->get();

        return view('admin.leave-balances.edit', compact('leaveBalance', 'employees', 'leaveTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeaveBalanceRequest $request, LeaveBalance $leaveBalance)
    {
        DB::beginTransaction();

        try {
            $oldData = $leaveBalance->toArray();
            $updatedLeaveBalance = $this->leaveBalanceService->update($leaveBalance, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-balances',
                'action' => 'update',
                'model' => 'LeaveBalance',
                'model_id' => $leaveBalance->id,
                'description' => 'Leave balance updated for employee #' . $leaveBalance->employee_id,
                'new_data' => $updatedLeaveBalance->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.leave-balances.index'),
                'message' => 'Leave balance updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveBalance $leaveBalance)
    {
        DB::beginTransaction();

        try {
            $oldData = $leaveBalance->toArray();

            $this->leaveBalanceService->delete($leaveBalance);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-balances',
                'action' => 'delete',
                'model' => 'LeaveBalance',
                'model_id' => $oldData['id'],
                'description' => 'Leave balance deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave balance deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status (AJAX switch toggle)
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
