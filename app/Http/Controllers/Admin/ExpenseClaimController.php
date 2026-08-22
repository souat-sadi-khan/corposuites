<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseClaimRequest;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use App\Models\WorkflowDefinition;
use App\Services\ExpenseClaimService;
use App\Services\WorkflowEngineService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ExpenseClaimController extends Controller
{
    use ActivityLogger;

    protected $expenseClaimService;

    public function __construct(ExpenseClaimService $expenseClaimService)
    {
        $this->expenseClaimService = $expenseClaimService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ExpenseClaim::query()->with(['employee', 'expenseCategory']);

            // Filter by status — only applied when the Advanced Search
            // "Record Status" field is actually set (same convention
            // Payroll/Salary Structures use once graduated to Adv Search).
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by expense category
            if ($request->expense_category_id) {
                $query->where('expense_category_id', $request->expense_category_id);
            }

            // Filter by approval status
            if ($request->approval_status) {
                $query->where('approval_status', $request->approval_status);
            }

            // Filter by reimbursement/payment status
            if ($request->payment_status) {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter by expense date range
            if ($request->filled('date_from')) {
                $query->whereDate('expense_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('expense_date', '<=', $request->date_to);
            }

            // Filter by amount range
            if ($request->filled('amount_min')) {
                $query->where('amount', '>=', $request->amount_min);
            }

            if ($request->filled('amount_max')) {
                $query->where('amount', '<=', $request->amount_max);
            }

            // Filter by receipt presence
            if ($request->filled('has_receipt')) {
                $request->has_receipt === '1'
                    ? $query->whereNotNull('receipt_path')
                    : $query->whereNull('receipt_path');
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('category_legacy', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('expenseCategory', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.expense-claims.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('category_summary', function ($row) {
                    $categoryName = $row->expenseCategory->name ?? ($row->category_legacy ?: 'Uncategorised');
                    return '<b class="tl-name-txt">' . e($categoryName) . '</b><br><small>' . number_format($row->amount, 2) . '</small>';
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
                ->addColumn('expense_date_formatted', function ($row) {
                    return $row->expense_date ? $row->expense_date->format('d-m-Y') : '-';
                })
                ->addColumn('receipt_link', function ($row) {
                    return $row->receipt_path
                        ? '<a href="' . asset('storage/' . $row->receipt_path) . '" target="_blank" class="tl-icon-btn" title="View Receipt"><i class="ri-download-2-line"></i></a>'
                        : '-';
                })
                ->addColumn('approval_badge', function ($row) {
                    $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    $color = $map[$row->approval_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . ucfirst($row->approval_status) . '</span>';
                })
                ->addColumn('payment_badge', function ($row) {
                    if ($row->payment_status === 'paid') {
                        $method = $row->reimbursement_method ? ' via ' . str_replace('_', ' ', ucfirst($row->reimbursement_method)) : '';
                        return '<span class="badge bg-success-subtle text-success">Reimbursed' . $method . '</span>'
                            . ($row->payment_date ? '<br><small>' . $row->payment_date->format('d-m-Y') . '</small>' : '');
                    }
                    return '<span class="badge bg-secondary-subtle text-secondary">Unpaid</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.expense-claims.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'category_summary', 'employee_name', 'receipt_link', 'approval_badge', 'payment_badge', 'action'])
                ->make(true);
        }

        $expenseCategories = ExpenseCategory::active()->orderBy('name')->get();
        $employees = Employee::active()->get();

        return view('admin.expense-claims.index', compact('expenseCategories', 'employees'));
    }

    /**
     * "How to use" documentation modal.
     */
    public function howTo()
    {
        return view('admin.expense-claims.doc');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();
        $expenseCategories = ExpenseCategory::active()->orderBy('name')->get();

        return view('admin.expense-claims.create', compact('employees', 'expenseCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExpenseClaimRequest $request)
    {
        DB::beginTransaction();

        try {
            $expenseClaim = $this->expenseClaimService->create($request->validated(), $request->file('receipt'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-claims',
                'action' => 'create',
                'model' => 'ExpenseClaim',
                'model_id' => $expenseClaim->id,
                'description' => 'Expense claim submitted for employee #' . $expenseClaim->employee_id,
                'new_data' => $expenseClaim->toArray(),
                'old_data' => null
            ]);

            // If an active Workflow Engine definition exists for ExpenseClaim, kick off
            // an approval instance. No definitions are seeded yet, so this is a no-op today.
            if (WorkflowDefinition::where('approvable_type', ExpenseClaim::class)->where('status', true)->exists()) {
                app(WorkflowEngineService::class)->start($expenseClaim, auth()->guard('admin')->id());
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense claim submitted successfully.'
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
    public function edit(ExpenseClaim $expenseClaim)
    {
        $employees = Employee::active()->get();
        $expenseCategories = ExpenseCategory::active()->orderBy('name')->get();

        return view('admin.expense-claims.edit', compact('expenseClaim', 'employees', 'expenseCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpenseClaimRequest $request, ExpenseClaim $expenseClaim)
    {
        DB::beginTransaction();

        try {
            $oldData = $expenseClaim->toArray();
            $updatedExpenseClaim = $this->expenseClaimService->update($expenseClaim, $request->validated(), $request->file('receipt'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-claims',
                'action' => 'update',
                'model' => 'ExpenseClaim',
                'model_id' => $expenseClaim->id,
                'description' => 'Expense claim updated for employee #' . $expenseClaim->employee_id,
                'new_data' => $updatedExpenseClaim->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.expense-claims.index'),
                'message' => 'Expense claim updated successfully.'
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
    public function destroy(ExpenseClaim $expenseClaim)
    {
        DB::beginTransaction();

        try {
            $oldData = $expenseClaim->toArray();

            $this->expenseClaimService->delete($expenseClaim);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-claims',
                'action' => 'delete',
                'model' => 'ExpenseClaim',
                'model_id' => $oldData['id'],
                'description' => 'Expense claim deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense claim deleted successfully.'
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
     * Approve the claim.
     */
    public function approve(ExpenseClaim $expenseClaim)
    {
        DB::beginTransaction();

        try {
            $oldData = $expenseClaim->toArray();

            // Fallback-safe: only route through the Workflow Engine when an active
            // WorkflowDefinition exists for ExpenseClaim. Today none exists, so this
            // always takes the else branch — the exact original behavior.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', ExpenseClaim::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $expenseClaim->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'approved');
                } else {
                    $this->expenseClaimService->approve($expenseClaim);
                }
            } else {
                $this->expenseClaimService->approve($expenseClaim);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-claims',
                'action' => 'approve',
                'model' => 'ExpenseClaim',
                'model_id' => $expenseClaim->id,
                'description' => 'Expense claim approved for employee #' . $expenseClaim->employee_id,
                'new_data' => $expenseClaim->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense claim approved.'
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
     * Reject the claim.
     */
    public function reject(ExpenseClaim $expenseClaim)
    {
        DB::beginTransaction();

        try {
            $oldData = $expenseClaim->toArray();

            // Fallback-safe: same pattern as approve() above.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', ExpenseClaim::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $expenseClaim->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'rejected');
                } else {
                    $this->expenseClaimService->reject($expenseClaim);
                }
            } else {
                $this->expenseClaimService->reject($expenseClaim);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-claims',
                'action' => 'reject',
                'model' => 'ExpenseClaim',
                'model_id' => $expenseClaim->id,
                'description' => 'Expense claim rejected for employee #' . $expenseClaim->employee_id,
                'new_data' => $expenseClaim->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense claim rejected.'
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
     * Mark an approved claim as reimbursed to the employee.
     */
    public function markReimbursed(Request $request, ExpenseClaim $expenseClaim)
    {
        $request->validate([
            'payment_date' => 'nullable|date',
            'reimbursement_method' => 'nullable|string|in:cash,bank_transfer,cheque,card,other',
        ]);

        try {
            $oldData = $expenseClaim->toArray();

            $this->expenseClaimService->markReimbursed(
                $expenseClaim,
                $request->input('payment_date'),
                $request->input('reimbursement_method')
            );

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-claims',
                'action' => 'mark-reimbursed',
                'model' => 'ExpenseClaim',
                'model_id' => $expenseClaim->id,
                'description' => 'Expense claim marked as reimbursed for employee #' . $expenseClaim->employee_id,
                'new_data' => $expenseClaim->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Expense claim marked as reimbursed.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
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

        $model = ExpenseClaim::find($id);
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
