<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkflowTemplateRequest;
use App\Models\WorkflowTemplate;
use App\Services\WorkflowTemplateService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class WorkflowTemplateController extends Controller
{
    use ActivityLogger;

    protected $workflowTemplateService;

    public function __construct(WorkflowTemplateService $workflowTemplateService)
    {
        $this->workflowTemplateService = $workflowTemplateService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = WorkflowTemplate::query();

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
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.workflow-templates.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function($row) {
                    return '<b class="tl-name-txt">'. $row->name . '</b><br><small>'. $row->description . '</small>';
                })
                ->addColumn('approval_mode_badge', function ($row) {
                    return '<span class="badge bg-light text-dark border">' . ucfirst($row->approval_mode) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.workflow-templates.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'approval_mode_badge', 'action'])
                ->make(true);
        }

        return view('admin.workflow-templates.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.workflow-templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkflowTemplateRequest $request)
    {
        DB::beginTransaction();

        try {
            $workflowTemplate = $this->workflowTemplateService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-templates',
                'action' => 'create',
                'model' => 'WorkflowTemplate',
                'model_id' => $workflowTemplate->id,
                'description' => 'Workflow Template "' . $workflowTemplate->name . '" created',
                'new_data' => $workflowTemplate->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Workflow template created successfully.'
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
    public function edit(WorkflowTemplate $workflowTemplate)
    {
        return view('admin.workflow-templates.edit', compact('workflowTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkflowTemplateRequest $request, WorkflowTemplate $workflowTemplate)
    {
        DB::beginTransaction();

        try {
            $oldData = $workflowTemplate->toArray();
            $updatedWorkflowTemplate = $this->workflowTemplateService->update($workflowTemplate, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-templates',
                'action' => 'update',
                'model' => 'WorkflowTemplate',
                'model_id' => $workflowTemplate->id,
                'description' => 'Workflow Template "' . $workflowTemplate->name . '" updated',
                'new_data' => $updatedWorkflowTemplate->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.workflow-templates.index'),
                'message' => 'Workflow template updated successfully.'
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
    public function destroy(WorkflowTemplate $workflowTemplate)
    {
        DB::beginTransaction();

        try {
            $oldData = $workflowTemplate->toArray();

            $this->workflowTemplateService->delete($workflowTemplate);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-templates',
                'action' => 'delete',
                'model' => 'WorkflowTemplate',
                'model_id' => $oldData['id'],
                'description' => 'Workflow Template "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Workflow template deleted successfully.'
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

        $model = WorkflowTemplate::find($id);
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
