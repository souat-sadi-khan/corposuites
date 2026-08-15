<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BudgetRequest;
use App\Models\Admin;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Services\BudgetService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BudgetController extends Controller
{
    use ActivityLogger;

    protected $budgetService;

    public function __construct(BudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Budget::with('approvedBy')->withCount('items');

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->period_type) {
                $query->where('period_type', $request->period_type);
            }

            if ($request->budget_status) {
                $query->where('budget_status', $request->budget_status);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('budget_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.budgets.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('budget_col', function ($row) {
                    $title = $row->title ?: 'Budget ' . $row->version_label;

                    return '<b class="tl-name-txt">' . e($title) . '</b><br><small>' . e($row->budget_code) . ' &middot; ' . e($row->version_label) . '</small>';
                })
                ->addColumn('period_col', function ($row) {
                    return e($row->period_label) . '<br><small>' . e($row->period_type_label) . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' ' . Str::plural('line', $row->items_count);
                })
                ->addColumn('total_formatted', function ($row) {
                    return '<b>' . number_format($row->total_amount, 2) . '</b>';
                })
                ->addColumn('budget_status_badge', function ($row) {
                    $map = [
                        'draft' => 'bg-secondary',
                        'approved' => 'bg-success',
                        'revised' => 'bg-warning',
                        'closed' => 'bg-dark',
                    ];

                    $badge = '<span class="badge ' . ($map[$row->budget_status] ?? 'bg-secondary') . '">' . e($row->budget_status_label) . '</span>';

                    if ($row->budget_status === 'approved' && $row->approved_date) {
                        $badge .= '<br><small>' . $row->approved_date->format('d M Y')
                            . ($row->approvedBy ? ' &middot; ' . e($row->approvedBy->name) : '') . '</small>';
                    }

                    return $badge;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.budgets.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'budget_col', 'period_col', 'total_formatted', 'budget_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.budgets.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.budgets.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BudgetRequest $request)
    {
        DB::beginTransaction();

        try {
            $budget = $this->budgetService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'budgets',
                'action' => 'create',
                'model' => 'Budget',
                'model_id' => $budget->id,
                'description' => 'Budget "' . $budget->budget_code . ' (' . $budget->version_label . ')" created',
                'new_data' => $budget->load('items')->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Budget created successfully.',
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
     * Show the form for editing the specified resource.
     */
    public function edit(Budget $budget)
    {
        $budget->load('items');

        return view('admin.budgets.edit', array_merge($this->formData(), compact('budget')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BudgetRequest $request, Budget $budget)
    {
        DB::beginTransaction();

        try {
            $oldData = $budget->load('items')->toArray();
            $updatedBudget = $this->budgetService->update($budget, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'budgets',
                'action' => 'update',
                'model' => 'Budget',
                'model_id' => $budget->id,
                'description' => 'Budget "' . $updatedBudget->budget_code . ' (' . $updatedBudget->version_label . ')" updated',
                'new_data' => $updatedBudget->load('items')->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.budgets.index'),
                'message' => 'Budget updated successfully.',
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
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget)
    {
        DB::beginTransaction();

        try {
            $oldData = $budget->load('items')->toArray();

            $this->budgetService->delete($budget);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'budgets',
                'action' => 'delete',
                'model' => 'Budget',
                'model_id' => $oldData['id'],
                'description' => 'Budget "' . $oldData['budget_code'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Budget deleted successfully.',
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
     * Update status (AJAX switch toggle)
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = Budget::find($id);
        if (! $model) {
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

    /**
     * Dropdown collections shared by create and edit. Only active, postable
     * (non-group) accounts are offered — defense-in-depth alongside the
     * Form Request's own group-account rejection, the same "exclude
     * invalid choices from the dropdown entirely" precedent Journal
     * Entries already established.
     */
    protected function formData(): array
    {
        return [
            'chartOfAccounts' => ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get(),
            'admins' => Admin::orderBy('name')->get(),
        ];
    }
}
