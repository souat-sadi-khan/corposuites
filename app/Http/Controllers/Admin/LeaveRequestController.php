<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveRequestRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\WorkflowDefinition;
use App\Services\LeaveRequestService;
use App\Services\WorkflowEngineService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeaveRequestController extends Controller
{
    use ActivityLogger;

    protected $leaveRequestService;

    public function __construct(LeaveRequestService $leaveRequestService)
    {
        $this->leaveRequestService = $leaveRequestService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LeaveRequest::query()->with(['employee', 'leaveType']);

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
                $query->where(function ($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.leave-requests.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('leave_type_name', function ($row) {
                    return $row->leaveType->name ?? '-';
                })
                ->addColumn('duration', function ($row) {
                    $start = $row->start_date ? $row->start_date->format('d-m-Y') : '-';
                    $end = $row->end_date ? $row->end_date->format('d-m-Y') : '-';
                    return $start . ' to ' . $end . '<br><small>' . $row->total_days . ' day(s)</small>';
                })
                ->addColumn('approval_badge', function ($row) {
                    $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    $color = $map[$row->approval_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . ucfirst($row->approval_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.leave-requests.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'duration', 'approval_badge', 'action'])
                ->make(true);
        }

        return view('admin.leave-requests.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();
        $leaveTypes = LeaveType::active()->get();

        return view('admin.leave-requests.create', compact('employees', 'leaveTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeaveRequestRequest $request)
    {
        DB::beginTransaction();

        try {
            $leaveRequest = $this->leaveRequestService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-requests',
                'action' => 'create',
                'model' => 'LeaveRequest',
                'model_id' => $leaveRequest->id,
                'description' => 'Leave request created for employee #' . $leaveRequest->employee_id,
                'new_data' => $leaveRequest->toArray(),
                'old_data' => null
            ]);

            // If an active Workflow Engine definition exists for LeaveRequest, kick off
            // an approval instance. No definitions are seeded yet, so this is a no-op today.
            if (WorkflowDefinition::where('approvable_type', LeaveRequest::class)->where('status', true)->exists()) {
                app(WorkflowEngineService::class)->start($leaveRequest, auth()->guard('admin')->id());
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave request created successfully.'
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
    public function edit(LeaveRequest $leaveRequest)
    {
        $employees = Employee::active()->get();
        $leaveTypes = LeaveType::active()->get();

        return view('admin.leave-requests.edit', compact('leaveRequest', 'employees', 'leaveTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeaveRequestRequest $request, LeaveRequest $leaveRequest)
    {
        DB::beginTransaction();

        try {
            $oldData = $leaveRequest->toArray();
            $updatedLeaveRequest = $this->leaveRequestService->update($leaveRequest, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-requests',
                'action' => 'update',
                'model' => 'LeaveRequest',
                'model_id' => $leaveRequest->id,
                'description' => 'Leave request updated for employee #' . $leaveRequest->employee_id,
                'new_data' => $updatedLeaveRequest->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.leave-requests.index'),
                'message' => 'Leave request updated successfully.'
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
    public function destroy(LeaveRequest $leaveRequest)
    {
        DB::beginTransaction();

        try {
            $oldData = $leaveRequest->toArray();

            $this->leaveRequestService->delete($leaveRequest);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-requests',
                'action' => 'delete',
                'model' => 'LeaveRequest',
                'model_id' => $oldData['id'],
                'description' => 'Leave request deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave request deleted successfully.'
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
     * Approve the request and deduct from leave balance.
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        DB::beginTransaction();

        try {
            $oldData = $leaveRequest->toArray();

            // Fallback-safe: only route through the Workflow Engine when an active
            // WorkflowDefinition exists for LeaveRequest. Today none exists, so this
            // always takes the else branch — the exact original behavior.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', LeaveRequest::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $leaveRequest->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'approved');
                } else {
                    $this->leaveRequestService->approve($leaveRequest);
                }
            } else {
                $this->leaveRequestService->approve($leaveRequest);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-requests',
                'action' => 'approve',
                'model' => 'LeaveRequest',
                'model_id' => $leaveRequest->id,
                'description' => 'Leave request approved for employee #' . $leaveRequest->employee_id,
                'new_data' => $leaveRequest->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave request approved.'
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
     * Reject the request.
     */
    public function reject(LeaveRequest $leaveRequest)
    {
        DB::beginTransaction();

        try {
            $oldData = $leaveRequest->toArray();

            // Fallback-safe: same pattern as approve() above.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', LeaveRequest::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $leaveRequest->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'rejected');
                } else {
                    $this->leaveRequestService->reject($leaveRequest);
                }
            } else {
                $this->leaveRequestService->reject($leaveRequest);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-requests',
                'action' => 'reject',
                'model' => 'LeaveRequest',
                'model_id' => $leaveRequest->id,
                'description' => 'Leave request rejected for employee #' . $leaveRequest->employee_id,
                'new_data' => $leaveRequest->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave request rejected.'
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

        $model = LeaveRequest::find($id);
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
