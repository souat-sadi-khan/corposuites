<?php

namespace App\Http\Controllers\Admin;

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
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('loan_summary', function ($row) {
                    return number_format($row->loan_amount, 2) . ' in ' . $row->installments . ' installment(s)<br><small>' . number_format($row->installment_amount, 2) . ' / installment</small>';
                })
                ->addColumn('balance', function ($row) {
                    return 'Paid: ' . number_format($row->paid_amount, 2) . '<br><small>Remaining: ' . number_format($row->remaining_balance, 2) . '</small>';
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

        return view('admin.employee-loans.index');
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
            $employeeLoan = $this->employeeLoanService->create($request->validated());

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
            $updatedEmployeeLoan = $this->employeeLoanService->update($employeeLoan, $request->validated());

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
