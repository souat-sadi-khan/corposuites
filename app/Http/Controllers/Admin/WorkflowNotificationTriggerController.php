<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkflowNotificationTriggerRequest;
use App\Models\Admin;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowNotificationTrigger;
use App\Services\WorkflowNotificationTriggerService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class WorkflowNotificationTriggerController extends Controller
{
    use ActivityLogger;

    /**
     * event => human label.
     */
    public const EVENT_LABELS = [
        'step_pending' => 'Step Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'resubmitted' => 'Resubmitted',
        'completed' => 'Completed',
    ];

    /**
     * notify_type => human label.
     */
    public const NOTIFY_TYPE_LABELS = [
        'role' => 'Role',
        'user' => 'User',
        'initiator' => 'Initiator',
        'approver' => 'Approver',
    ];

    protected $workflowNotificationTriggerService;

    public function __construct(WorkflowNotificationTriggerService $workflowNotificationTriggerService)
    {
        $this->workflowNotificationTriggerService = $workflowNotificationTriggerService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = WorkflowNotificationTrigger::query()->with('workflowDefinition');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by workflow definition
            if ($request->workflow_definition_id) {
                $query->where('workflow_definition_id', $request->workflow_definition_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('event', 'like', "%{$search}%")
                      ->orWhere('notify_type', 'like', "%{$search}%")
                      ->orWhere('template_message', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.workflow-notification-triggers.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('event_badge', function ($row) {
                    return '<span class="badge bg-light text-dark border">' . (self::EVENT_LABELS[$row->event] ?? $row->event) . '</span>';
                })
                ->addColumn('notify', function ($row) {
                    $label = self::NOTIFY_TYPE_LABELS[$row->notify_type] ?? $row->notify_type;
                    $target = '';
                    if ($row->notify_type === 'role' && $row->notify_id) {
                        $role = Role::find($row->notify_id);
                        $target = $role ? ' - ' . $role->name : '';
                    } elseif ($row->notify_type === 'user' && $row->notify_id) {
                        $admin = Admin::find($row->notify_id);
                        $target = $admin ? ' - ' . $admin->name : '';
                    }
                    return '<b class="tl-name-txt">' . $label . '</b>' . e($target);
                })
                ->addColumn('message', function ($row) {
                    return $row->template_message ? \Illuminate\Support\Str::limit($row->template_message, 60) : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.workflow-notification-triggers.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'event_badge', 'notify', 'action'])
                ->make(true);
        }

        $workflowDefinitionId = $request->workflow_definition_id;
        $workflowDefinition = $workflowDefinitionId ? WorkflowDefinition::find($workflowDefinitionId) : null;

        return view('admin.workflow-notification-triggers.index', compact('workflowDefinitionId', 'workflowDefinition'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $workflowDefinitionId = $request->workflow_definition_id;
        $workflowDefinitions = WorkflowDefinition::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $admins = Admin::orderBy('name')->get();

        return view('admin.workflow-notification-triggers.create', compact(
            'workflowDefinitionId', 'workflowDefinitions', 'roles', 'admins'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkflowNotificationTriggerRequest $request)
    {
        DB::beginTransaction();

        try {
            $workflowNotificationTrigger = $this->workflowNotificationTriggerService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-notification-triggers',
                'action' => 'create',
                'model' => 'WorkflowNotificationTrigger',
                'model_id' => $workflowNotificationTrigger->id,
                'description' => 'Workflow Notification Trigger for event "' . $workflowNotificationTrigger->event . '" created',
                'new_data' => $workflowNotificationTrigger->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Workflow notification trigger created successfully.'
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
    public function edit(WorkflowNotificationTrigger $workflowNotificationTrigger)
    {
        $workflowDefinitionId = $workflowNotificationTrigger->workflow_definition_id;
        $workflowDefinitions = WorkflowDefinition::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $admins = Admin::orderBy('name')->get();

        return view('admin.workflow-notification-triggers.edit', compact(
            'workflowNotificationTrigger', 'workflowDefinitionId', 'workflowDefinitions', 'roles', 'admins'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkflowNotificationTriggerRequest $request, WorkflowNotificationTrigger $workflowNotificationTrigger)
    {
        DB::beginTransaction();

        try {
            $oldData = $workflowNotificationTrigger->toArray();
            $updatedWorkflowNotificationTrigger = $this->workflowNotificationTriggerService->update($workflowNotificationTrigger, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-notification-triggers',
                'action' => 'update',
                'model' => 'WorkflowNotificationTrigger',
                'model_id' => $workflowNotificationTrigger->id,
                'description' => 'Workflow Notification Trigger for event "' . $workflowNotificationTrigger->event . '" updated',
                'new_data' => $updatedWorkflowNotificationTrigger->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.workflow-notification-triggers.index', ['workflow_definition_id' => $updatedWorkflowNotificationTrigger->workflow_definition_id]),
                'message' => 'Workflow notification trigger updated successfully.'
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
    public function destroy(WorkflowNotificationTrigger $workflowNotificationTrigger)
    {
        DB::beginTransaction();

        try {
            $oldData = $workflowNotificationTrigger->toArray();

            $this->workflowNotificationTriggerService->delete($workflowNotificationTrigger);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'workflow-notification-triggers',
                'action' => 'delete',
                'model' => 'WorkflowNotificationTrigger',
                'model_id' => $oldData['id'],
                'description' => 'Workflow Notification Trigger for event "' . $oldData['event'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Workflow notification trigger deleted successfully.'
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

        $model = WorkflowNotificationTrigger::find($id);
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
