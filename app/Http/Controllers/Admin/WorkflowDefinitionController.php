<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkflowDefinitionRequest;
use App\Models\Admin;
use App\Models\Designation;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowTemplate;
use App\Services\WorkflowDefinitionService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class WorkflowDefinitionController extends Controller
{
    use ActivityLogger;

    /**
     * module_key => human label, shown in the builder + datatable.
     */
    public const MODULE_LABELS = [
        'leave_request' => 'Leave Request',
        'expense_claim' => 'Expense Claim',
        'attendance_adjustment' => 'Attendance Adjustment',
        'employee_loan' => 'Employee Loan',
        'purchase_request' => 'Purchase Request',
    ];

    protected $workflowDefinitionService;

    public function __construct(WorkflowDefinitionService $workflowDefinitionService)
    {
        $this->workflowDefinitionService = $workflowDefinitionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = WorkflowDefinition::withCount('steps');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('module_key', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('module_label', function ($row) {
                    return self::MODULE_LABELS[$row->module_key] ?? $row->module_key;
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b>';
                })
                ->addColumn('approval_mode_badge', function ($row) {
                    return '<span class="badge bg-light text-dark border">' . ucfirst($row->approval_mode) . '</span>';
                })
                ->addColumn('steps_count_badge', function ($row) {
                    return '<span class="badge bg-light text-dark border">' . $row->steps_count . ' step(s)</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.workflow-definitions.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.workflow-definitions.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'approval_mode_badge', 'steps_count_badge', 'action'])
                ->make(true);
        }

        return view('admin.workflow-definitions.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $moduleOptions = self::MODULE_LABELS;
        $workflowTemplates = WorkflowTemplate::where('status', 1)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $admins = Admin::orderBy('name')->get();
        $designations = Designation::where('status', 1)->orderBy('name')->get();

        return view('admin.workflow-definitions.create', compact(
            'moduleOptions', 'workflowTemplates', 'roles', 'admins', 'designations'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkflowDefinitionRequest $request)
    {
        DB::beginTransaction();

        try {
            $workflowDefinition = $this->workflowDefinitionService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-definitions',
                'action' => 'create',
                'model' => 'WorkflowDefinition',
                'model_id' => $workflowDefinition->id,
                'description' => 'Workflow Definition "' . $workflowDefinition->name . '" created',
                'new_data' => $workflowDefinition->load('steps.approvers')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.workflow-definitions.index'),
                'message' => 'Workflow definition created successfully.'
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
    public function edit(WorkflowDefinition $workflowDefinition)
    {
        $workflowDefinition->load('steps.approvers');

        $moduleOptions = self::MODULE_LABELS;
        $workflowTemplates = WorkflowTemplate::where('status', 1)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $admins = Admin::orderBy('name')->get();
        $designations = Designation::where('status', 1)->orderBy('name')->get();

        return view('admin.workflow-definitions.edit', compact(
            'workflowDefinition', 'moduleOptions', 'workflowTemplates', 'roles', 'admins', 'designations'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkflowDefinitionRequest $request, WorkflowDefinition $workflowDefinition)
    {
        DB::beginTransaction();

        try {
            $oldData = $workflowDefinition->load('steps.approvers')->toArray();
            $updatedWorkflowDefinition = $this->workflowDefinitionService->update($workflowDefinition, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-definitions',
                'action' => 'update',
                'model' => 'WorkflowDefinition',
                'model_id' => $workflowDefinition->id,
                'description' => 'Workflow Definition "' . $workflowDefinition->name . '" updated',
                'new_data' => $updatedWorkflowDefinition->load('steps.approvers')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.workflow-definitions.index'),
                'message' => 'Workflow definition updated successfully.'
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
    public function destroy(WorkflowDefinition $workflowDefinition)
    {
        DB::beginTransaction();

        try {
            $oldData = $workflowDefinition->load('steps.approvers')->toArray();

            $this->workflowDefinitionService->delete($workflowDefinition);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-definitions',
                'action' => 'delete',
                'model' => 'WorkflowDefinition',
                'model_id' => $oldData['id'],
                'description' => 'Workflow Definition "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Workflow definition deleted successfully.'
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

        $model = WorkflowDefinition::find($id);
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
