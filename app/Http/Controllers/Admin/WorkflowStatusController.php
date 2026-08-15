<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkflowStatusRequest;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStatus;
use App\Services\WorkflowStatusService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class WorkflowStatusController extends Controller
{
    use ActivityLogger;

    protected $workflowStatusService;

    public function __construct(WorkflowStatusService $workflowStatusService)
    {
        $this->workflowStatusService = $workflowStatusService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = WorkflowStatus::query()->with('workflowDefinition');

            // Filter by workflow definition
            if ($request->workflow_definition_id) {
                $query->where('workflow_definition_id', $request->workflow_definition_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('key', 'like', "%{$search}%")
                      ->orWhere('label', 'like', "%{$search}%");
                });
            }

            $query->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');

            return DataTables::eloquent($query)
                ->addColumn('key_label', function ($row) {
                    return '<b class="tl-name-txt">' . $row->key . '</b>';
                })
                ->addColumn('label', function ($row) {
                    return $row->label;
                })
                ->addColumn('color_swatch', function ($row) {
                    if (!$row->color) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="d-inline-block rounded-circle border me-1" style="width:14px;height:14px;background-color:' . e($row->color) . ';"></span>' . e($row->color);
                })
                ->addColumn('is_terminal_badge', function ($row) {
                    return $row->is_terminal
                        ? '<span class="badge bg-success-subtle text-success">Yes</span>'
                        : '<span class="badge bg-light text-dark border">No</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.workflow-statuses.action', compact('row'))->render();
                })
                ->rawColumns(['key_label', 'color_swatch', 'is_terminal_badge', 'action'])
                ->make(true);
        }

        $workflowDefinitionId = $request->workflow_definition_id;
        $workflowDefinition = $workflowDefinitionId ? WorkflowDefinition::find($workflowDefinitionId) : null;

        return view('admin.workflow-statuses.index', compact('workflowDefinitionId', 'workflowDefinition'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $workflowDefinitionId = $request->workflow_definition_id;
        $workflowDefinitions = WorkflowDefinition::orderBy('name')->get();

        return view('admin.workflow-statuses.create', compact('workflowDefinitionId', 'workflowDefinitions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkflowStatusRequest $request)
    {
        DB::beginTransaction();

        try {
            $workflowStatus = $this->workflowStatusService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-statuses',
                'action' => 'create',
                'model' => 'WorkflowStatus',
                'model_id' => $workflowStatus->id,
                'description' => 'Workflow Status "' . $workflowStatus->label . '" created',
                'new_data' => $workflowStatus->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Workflow status created successfully.'
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
    public function edit(WorkflowStatus $workflowStatus)
    {
        $workflowDefinitionId = $workflowStatus->workflow_definition_id;
        $workflowDefinitions = WorkflowDefinition::orderBy('name')->get();

        return view('admin.workflow-statuses.edit', compact('workflowStatus', 'workflowDefinitionId', 'workflowDefinitions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkflowStatusRequest $request, WorkflowStatus $workflowStatus)
    {
        DB::beginTransaction();

        try {
            $oldData = $workflowStatus->toArray();
            $updatedWorkflowStatus = $this->workflowStatusService->update($workflowStatus, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-statuses',
                'action' => 'update',
                'model' => 'WorkflowStatus',
                'model_id' => $workflowStatus->id,
                'description' => 'Workflow Status "' . $workflowStatus->label . '" updated',
                'new_data' => $updatedWorkflowStatus->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.workflow-statuses.index', ['workflow_definition_id' => $updatedWorkflowStatus->workflow_definition_id]),
                'message' => 'Workflow status updated successfully.'
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
    public function destroy(WorkflowStatus $workflowStatus)
    {
        DB::beginTransaction();

        try {
            $oldData = $workflowStatus->toArray();

            $this->workflowStatusService->delete($workflowStatus);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-statuses',
                'action' => 'delete',
                'model' => 'WorkflowStatus',
                'model_id' => $oldData['id'],
                'description' => 'Workflow Status "' . $oldData['label'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Workflow status deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
