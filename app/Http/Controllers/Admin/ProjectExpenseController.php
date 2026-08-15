<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectExpenseRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\Vendor;
use App\Services\ProjectExpenseService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectExpenseController extends Controller
{
    use ActivityLogger;

    protected $projectExpenseService;

    public function __construct(ProjectExpenseService $projectExpenseService)
    {
        $this->projectExpenseService = $projectExpenseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectExpense::with(['project.client', 'vendor', 'employee']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->expense_category) {
                $query->where('expense_category', $request->expense_category);
            }

            if ($request->approval_status) {
                $query->where('approval_status', $request->approval_status);
            }

            if ($request->billable !== null && $request->billable !== '') {
                $query->where('is_billable', $request->billable);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%")
                                ->orWhere('project_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('vendor', function ($v) use ($search) {
                            $v->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('expense_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.project-expenses.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('title_col', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->title) . '</b><br><small>' . e($row->expense_category_label) . '</small>';
                })
                ->addColumn('project_name', function ($row) {
                    if (! $row->project) {
                        return '<span class="text-danger">Project removed</span>';
                    }

                    return e($row->project->name) . '<br><small>' . e($row->project->project_code)
                        . ($row->project->client ? ' · ' . e($row->project->client->name) : '') . '</small>';
                })
                ->addColumn('paid_to', function ($row) {
                    $bits = [];

                    if ($row->vendor) {
                        $bits[] = e($row->vendor->name);
                    }

                    if ($row->employee) {
                        $bits[] = e($row->employee->first_name . ' ' . $row->employee->last_name);
                    }

                    return $bits ? implode('<br>', $bits) : '<span class="text-muted">—</span>';
                })
                ->addColumn('expense_date_formatted', function ($row) {
                    return $row->expense_date->format('d M Y');
                })
                ->addColumn('amount_col', function ($row) {
                    $line = number_format($row->amount, 2);
                    $line .= $row->is_billable
                        ? '<br><span class="badge bg-success">Billable</span>'
                        : '<br><span class="badge bg-secondary">Non-billable</span>';

                    return $line;
                })
                ->addColumn('receipt_link', function ($row) {
                    return $row->receipt_path
                        ? '<a href="' . asset('storage/' . $row->receipt_path) . '" target="_blank" class="tl-icon-btn" title="View Receipt"><i class="ri-download-2-line"></i></a>'
                        : '<span class="text-muted">—</span>';
                })
                ->addColumn('approval_badge', function ($row) {
                    $map = ['pending' => 'bg-warning', 'approved' => 'bg-success', 'rejected' => 'bg-danger'];

                    return '<span class="badge ' . ($map[$row->approval_status] ?? 'bg-secondary') . '">' . e($row->approval_status_label) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.project-expenses.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'title_col', 'project_name', 'paid_to', 'amount_col', 'receipt_link', 'approval_badge', 'action'])
                ->make(true);
        }

        return view('admin.project-expenses.index', $this->formData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-expenses.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectExpenseRequest $request)
    {
        DB::beginTransaction();

        try {
            $expense = $this->projectExpenseService->create($request->validated(), $request->file('receipt'));
            $expense->load('project');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-expenses',
                'action' => 'create',
                'model' => 'ProjectExpense',
                'model_id' => $expense->id,
                'description' => 'Expense "' . $this->expenseLabel($expense) . '" recorded',
                'new_data' => $expense->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense recorded successfully.'
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
    public function edit(ProjectExpense $projectExpense)
    {
        return view('admin.project-expenses.edit', array_merge($this->formData(), [
            'projectExpense' => $projectExpense,
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectExpenseRequest $request, ProjectExpense $projectExpense)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectExpense->toArray();
            $updated = $this->projectExpenseService->update($projectExpense, $request->validated(), $request->file('receipt'));
            $updated->load('project');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-expenses',
                'action' => 'update',
                'model' => 'ProjectExpense',
                'model_id' => $projectExpense->id,
                'description' => 'Expense "' . $this->expenseLabel($updated) . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.project-expenses.index'),
                'message' => 'Expense updated successfully.'
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
    public function destroy(ProjectExpense $projectExpense)
    {
        DB::beginTransaction();

        try {
            $projectExpense->load('project');
            $oldData = $projectExpense->toArray();
            $label = $this->expenseLabel($projectExpense);

            $this->projectExpenseService->delete($projectExpense);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-expenses',
                'action' => 'delete',
                'model' => 'ProjectExpense',
                'model_id' => $oldData['id'],
                'description' => 'Expense "' . $label . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense deleted successfully.'
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

        $model = ProjectExpense::find($id);
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

    public function approve(ProjectExpense $projectExpense)
    {
        try {
            $expense = $this->projectExpenseService->approve($projectExpense, auth()->guard('admin')->id());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-expenses',
                'action' => 'approve',
                'model' => 'ProjectExpense',
                'model_id' => $expense->id,
                'description' => 'Expense "' . $this->expenseLabel($expense) . '" approved',
                'new_data' => $expense->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Expense approved.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function reject(ProjectExpense $projectExpense)
    {
        try {
            $expense = $this->projectExpenseService->reject($projectExpense);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-expenses',
                'action' => 'reject',
                'model' => 'ProjectExpense',
                'model_id' => $expense->id,
                'description' => 'Expense "' . $this->expenseLabel($expense) . '" rejected',
                'new_data' => $expense->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Expense rejected.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    protected function expenseLabel(ProjectExpense $expense): string
    {
        return $expense->title . ' on ' . ($expense->project->project_code ?? 'unknown project');
    }

    /**
     * Dropdown collections shared by index, create and edit.
     */
    protected function formData(): array
    {
        return [
            'projects' => Project::active()->with('client')->orderBy('name')->get(),
            'vendors' => Vendor::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ];
    }
}
