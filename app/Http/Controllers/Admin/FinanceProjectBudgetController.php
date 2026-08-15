<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinanceProjectBudgetRequest;
use App\Models\Admin;
use App\Models\ChartOfAccount;
use App\Models\FinanceProjectBudget;
use App\Models\Project;
use App\Services\FinanceProjectBudgetService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class FinanceProjectBudgetController extends Controller
{
    use ActivityLogger;

    protected $financeProjectBudgetService;

    public function __construct(FinanceProjectBudgetService $financeProjectBudgetService)
    {
        $this->financeProjectBudgetService = $financeProjectBudgetService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FinanceProjectBudget::with(['project.client', 'approvedBy'])->withCount('items');

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
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
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%")
                                ->orWhere('project_code', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.finance-project-budgets.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('budget_col', function ($row) {
                    $title = $row->title ?: 'Budget ' . $row->version_label;

                    return '<b class="tl-name-txt">' . e($title) . '</b><br><small>' . e($row->budget_code) . ' &middot; ' . e($row->version_label) . '</small>';
                })
                ->addColumn('project_name', function ($row) {
                    if (! $row->project) {
                        return '<span class="text-danger">Project removed</span>';
                    }

                    return e($row->project->name) . '<br><small>' . e($row->project->project_code)
                        . ($row->project->client ? ' &middot; ' . e($row->project->client->name) : '') . '</small>';
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
                    return view('admin.finance-project-budgets.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'budget_col', 'project_name', 'period_col', 'total_formatted', 'budget_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.finance-project-budgets.index', [
            'projects' => Project::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.finance-project-budgets.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FinanceProjectBudgetRequest $request)
    {
        DB::beginTransaction();

        try {
            $budget = $this->financeProjectBudgetService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'finance-project-budgets',
                'action' => 'create',
                'model' => 'FinanceProjectBudget',
                'model_id' => $budget->id,
                'description' => 'Project Budget "' . $budget->budget_code . ' (' . $budget->version_label . ')" created',
                'new_data' => $budget->load('items')->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Project budget created successfully.',
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
    public function edit(FinanceProjectBudget $financeProjectBudget)
    {
        $financeProjectBudget->load('items');

        return view('admin.finance-project-budgets.edit', array_merge($this->formData(), ['financeProjectBudget' => $financeProjectBudget]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FinanceProjectBudgetRequest $request, FinanceProjectBudget $financeProjectBudget)
    {
        DB::beginTransaction();

        try {
            $oldData = $financeProjectBudget->load('items')->toArray();
            $updatedBudget = $this->financeProjectBudgetService->update($financeProjectBudget, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'finance-project-budgets',
                'action' => 'update',
                'model' => 'FinanceProjectBudget',
                'model_id' => $financeProjectBudget->id,
                'description' => 'Project Budget "' . $updatedBudget->budget_code . ' (' . $updatedBudget->version_label . ')" updated',
                'new_data' => $updatedBudget->load('items')->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.finance-project-budgets.index'),
                'message' => 'Project budget updated successfully.',
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
    public function destroy(FinanceProjectBudget $financeProjectBudget)
    {
        DB::beginTransaction();

        try {
            $oldData = $financeProjectBudget->load('items')->toArray();

            $this->financeProjectBudgetService->delete($financeProjectBudget);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'finance-project-budgets',
                'action' => 'delete',
                'model' => 'FinanceProjectBudget',
                'model_id' => $oldData['id'],
                'description' => 'Project Budget "' . $oldData['budget_code'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Project budget deleted successfully.',
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

        $model = FinanceProjectBudget::find($id);
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
     * Form Request's own group-account rejection, the same precedent
     * Budget Planning/Department Budget/Journal Entries already
     * established.
     */
    protected function formData(): array
    {
        return [
            'projects' => Project::active()->with('client')->orderBy('name')->get(),
            'chartOfAccounts' => ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get(),
            'admins' => Admin::orderBy('name')->get(),
        ];
    }
}
