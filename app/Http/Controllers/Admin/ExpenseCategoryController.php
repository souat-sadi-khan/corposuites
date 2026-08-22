<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseCategoryRequest;
use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use App\Services\ExpenseCategoryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ExpenseCategoryController extends Controller
{
    use ActivityLogger;

    protected $expenseCategoryService;

    public function __construct(ExpenseCategoryService $expenseCategoryService)
    {
        $this->expenseCategoryService = $expenseCategoryService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ExpenseCategory::query()->with('chartOfAccount')->withCount('expenseClaims');

            // Record status — only applied when the Advanced Search
            // "Record Status" field is actually set (same convention
            // Payroll/Salary Structures use once graduated to Adv Search).
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Has a spending cap configured
            if ($request->filled('has_limit')) {
                $request->has_limit === '1'
                    ? $query->whereNotNull('max_amount_per_claim')
                    : $query->whereNull('max_amount_per_claim');
            }

            // Requires a receipt above some threshold
            if ($request->filled('receipt_required')) {
                $request->receipt_required === '1'
                    ? $query->whereNotNull('receipt_required_above')
                    : $query->whereNull('receipt_required_above');
            }

            // GL account mapping
            if ($request->filled('chart_of_account_id')) {
                $request->chart_of_account_id === 'none'
                    ? $query->whereNull('chart_of_account_id')
                    : $query->where('chart_of_account_id', $request->chart_of_account_id);
            }

            // Max amount per claim range
            if ($request->filled('max_amount_min')) {
                $query->where('max_amount_per_claim', '>=', $request->max_amount_min);
            }

            if ($request->filled('max_amount_max')) {
                $query->where('max_amount_per_claim', '<=', $request->max_amount_max);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.expense-categories.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name_col', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b>' . ($row->description ? '<br><small>' . e($row->description) . '</small>' : '');
                })
                ->addColumn('policy_col', function ($row) {
                    $lines = [];

                    $lines[] = $row->max_amount_per_claim !== null
                        ? 'Max: ' . number_format($row->max_amount_per_claim, 2)
                        : 'No max limit';

                    $lines[] = $row->receipt_required_above !== null
                        ? 'Receipt required above ' . number_format($row->receipt_required_above, 2)
                        : 'Receipt always optional';

                    return implode('<br><small>', $lines) === $lines[0]
                        ? $lines[0]
                        : $lines[0] . '<br><small>' . $lines[1] . '</small>';
                })
                ->addColumn('gl_account_col', function ($row) {
                    return $row->chartOfAccount
                        ? $row->chartOfAccount->code . ' - ' . $row->chartOfAccount->name
                        : '<span class="text-muted">Not mapped</span>';
                })
                ->addColumn('claims_count_label', function ($row) {
                    return $row->expense_claims_count;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.expense-categories.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name_col', 'policy_col', 'gl_account_col', 'action'])
                ->make(true);
        }

        $chartOfAccounts = $this->postableAccounts();

        return view('admin.expense-categories.index', compact('chartOfAccounts'));
    }

    /**
     * "How to use" documentation modal.
     */
    public function howTo()
    {
        return view('admin.expense-categories.doc');
    }

    public function create()
    {
        $chartOfAccounts = $this->postableAccounts();

        return view('admin.expense-categories.create', compact('chartOfAccounts'));
    }

    public function store(ExpenseCategoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $expenseCategory = $this->expenseCategoryService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-categories',
                'action' => 'create',
                'model' => 'ExpenseCategory',
                'model_id' => $expenseCategory->id,
                'description' => 'Expense category "' . $expenseCategory->name . '" created',
                'new_data' => $expenseCategory->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense category created successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        $chartOfAccounts = $this->postableAccounts();

        return view('admin.expense-categories.edit', compact('expenseCategory', 'chartOfAccounts'));
    }

    public function update(ExpenseCategoryRequest $request, ExpenseCategory $expenseCategory)
    {
        DB::beginTransaction();

        try {
            $oldData = $expenseCategory->toArray();
            $updated = $this->expenseCategoryService->update($expenseCategory, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-categories',
                'action' => 'update',
                'model' => 'ExpenseCategory',
                'model_id' => $expenseCategory->id,
                'description' => 'Expense category "' . $updated->name . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.expense-categories.index'),
                'message' => 'Expense category updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        DB::beginTransaction();

        try {
            $oldData = $expenseCategory->toArray();

            $this->expenseCategoryService->delete($expenseCategory);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'expense-categories',
                'action' => 'delete',
                'model' => 'ExpenseCategory',
                'model_id' => $oldData['id'],
                'description' => 'Expense category "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense category deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = ExpenseCategory::find($id);
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.',
        ]);
    }

    protected function postableAccounts()
    {
        return ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get();
    }
}
