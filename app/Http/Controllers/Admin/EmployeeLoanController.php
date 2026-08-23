<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeLoanRequest;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\WorkflowDefinition;
use App\Services\EmployeeLoanService;
use App\Services\WorkflowEngineService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmployeeLoanController extends Controller
{
    use ActivityLogger;

    protected $employeeLoanService;

    public function __construct(EmployeeLoanService $employeeLoanService)
    {
        $this->employeeLoanService = $employeeLoanService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeLoan::query()->with('employee');

            // Record status — only applied when the Advanced Search
            // "Record Status" field is actually set (same convention
            // Payroll/Salary Structures use once graduated to Adv Search).
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by approval status
            if ($request->approval_status) {
                $query->where('approval_status', $request->approval_status);
            }

            // Filter by whether the loan is opted in to automatic salary deduction
            if ($request->filled('deduct_from_salary')) {
                $query->where('deduct_from_salary', $request->deduct_from_salary);
            }

            // Filter by payment state (derived from paid_amount vs loan_amount)
            if ($request->payment_state === 'fully_paid') {
                $query->whereColumn('paid_amount', '>=', 'loan_amount');
            } elseif ($request->payment_state === 'outstanding') {
                $query->whereColumn('paid_amount', '<', 'loan_amount');
            }

            // Filter by loan amount range
            if ($request->filled('loan_amount_min')) {
                $query->where('loan_amount', '>=', $request->loan_amount_min);
            }

            if ($request->filled('loan_amount_max')) {
                $query->where('loan_amount', '<=', $request->loan_amount_max);
            }

            // Filter by start date range
            if ($request->filled('start_date_from')) {
                $query->whereDate('start_date', '>=', $request->start_date_from);
            }

            if ($request->filled('start_date_to')) {
                $query->whereDate('start_date', '<=', $request->start_date_to);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.employee-loans.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    $avatar = Images::show($row->employee->photo);

                    return '
                        <div class="d-flex align-items-center">
                            <div class="mr-2 employee-avatar">
                                ' . $avatar . '
                            </div>
                            <div>
                                <b class="tl-name-txt">' . e($row->employee->full_name) . '</b>
                                <br>
                                <small>' . e($row->employee->employee_code) . '</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('loan_summary', function ($row) {
                    $line = format_currency($row->loan_amount) . ' in ' . $row->installments . ' installment(s)<br><small>' . format_currency($row->installment_amount) . ' / installment</small>';

                    $line .= $row->deduct_from_salary
                        ? '<br><span class="badge bg-info-subtle text-info">Auto-deducted from salary</span>'
                        : '<br><span class="badge bg-secondary-subtle text-secondary">Manual repayment only</span>';

                    return $line;
                })
                ->addColumn('balance', function ($row) {
                    return 'Paid: ' . format_currency($row->paid_amount) . '<br><small>Remaining: ' . format_currency($row->remaining_balance) . '</small>';
                })
                ->addColumn('approval_badge', function ($row) {
                    $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    $color = $map[$row->approval_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . ucfirst($row->approval_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.employee-loans.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'loan_summary', 'balance', 'approval_badge', 'action'])
                ->make(true);
        }

        $employees = Employee::active()->get();

        return view('admin.employee-loans.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.employee-loans.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeLoanRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['deduct_from_salary'] = $request->boolean('deduct_from_salary');

            $employeeLoan = $this->employeeLoanService->create($data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-loans',
                'action' => 'create',
                'model' => 'EmployeeLoan',
                'model_id' => $employeeLoan->id,
                'description' => 'Employee loan requested for employee #' . $employeeLoan->employee_id,
                'new_data' => $employeeLoan->toArray(),
                'old_data' => null
            ]);

            // If an active Workflow Engine definition exists for EmployeeLoan, kick off
            // an approval instance. No definitions are seeded yet, so this is a no-op today.
            if (WorkflowDefinition::where('approvable_type', EmployeeLoan::class)->where('status', true)->exists()) {
                app(WorkflowEngineService::class)->start($employeeLoan, auth()->guard('admin')->id());
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee loan created successfully.'
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
    public function edit(EmployeeLoan $employeeLoan)
    {
        $employees = Employee::active()->get();

        return view('admin.employee-loans.edit', compact('employeeLoan', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeLoanRequest $request, EmployeeLoan $employeeLoan)
    {
        DB::beginTransaction();

        try {
            $oldData = $employeeLoan->toArray();

            $data = $request->validated();
            $data['deduct_from_salary'] = $request->boolean('deduct_from_salary');

            $updatedEmployeeLoan = $this->employeeLoanService->update($employeeLoan, $data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-loans',
                'action' => 'update',
                'model' => 'EmployeeLoan',
                'model_id' => $employeeLoan->id,
                'description' => 'Employee loan updated for employee #' . $employeeLoan->employee_id,
                'new_data' => $updatedEmployeeLoan->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.employee-loans.index'),
                'message' => 'Employee loan updated successfully.'
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
    public function destroy(EmployeeLoan $employeeLoan)
    {
        DB::beginTransaction();

        try {
            $oldData = $employeeLoan->toArray();

            $this->employeeLoanService->delete($employeeLoan);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-loans',
                'action' => 'delete',
                'model' => 'EmployeeLoan',
                'model_id' => $oldData['id'],
                'description' => 'Employee loan deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee loan deleted successfully.'
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
     * Approve the loan.
     */
    public function approve(EmployeeLoan $employeeLoan)
    {
        DB::beginTransaction();

        try {
            $oldData = $employeeLoan->toArray();

            // Fallback-safe: only route through the Workflow Engine when an active
            // WorkflowDefinition exists for EmployeeLoan. Today none exists, so this
            // always takes the else branch — the exact original behavior.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', EmployeeLoan::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $employeeLoan->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'approved');
                } else {
                    $this->employeeLoanService->approve($employeeLoan);
                }
            } else {
                $this->employeeLoanService->approve($employeeLoan);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-loans',
                'action' => 'approve',
                'model' => 'EmployeeLoan',
                'model_id' => $employeeLoan->id,
                'description' => 'Employee loan approved for employee #' . $employeeLoan->employee_id,
                'new_data' => $employeeLoan->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee loan approved.'
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
     * Reject the loan.
     */
    public function reject(EmployeeLoan $employeeLoan)
    {
        DB::beginTransaction();

        try {
            $oldData = $employeeLoan->toArray();

            // Fallback-safe: same pattern as approve() above.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', EmployeeLoan::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $employeeLoan->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'rejected');
                } else {
                    $this->employeeLoanService->reject($employeeLoan);
                }
            } else {
                $this->employeeLoanService->reject($employeeLoan);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-loans',
                'action' => 'reject',
                'model' => 'EmployeeLoan',
                'model_id' => $employeeLoan->id,
                'description' => 'Employee loan rejected for employee #' . $employeeLoan->employee_id,
                'new_data' => $employeeLoan->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee loan rejected.'
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
     * Record a repayment against the loan.
     */
    public function recordPayment(Request $request, EmployeeLoan $employeeLoan)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            $oldData = $employeeLoan->toArray();
            $this->employeeLoanService->recordPayment($employeeLoan, (float) $request->amount);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-loans',
                'action' => 'record-payment',
                'model' => 'EmployeeLoan',
                'model_id' => $employeeLoan->id,
                'description' => 'Payment recorded for employee loan #' . $employeeLoan->id,
                'new_data' => $employeeLoan->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment recorded successfully.'
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

        $model = EmployeeLoan::find($id);
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
