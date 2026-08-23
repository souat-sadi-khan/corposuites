<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveRequestRequest;
use App\Helpers\Images;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\WorkflowDefinition;
use App\Services\LeavePolicyService;
use App\Services\LeaveAttendanceService;
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
    protected $leavePolicyService;

    public function __construct(LeaveRequestService $leaveRequestService, LeavePolicyService $leavePolicyService, protected LeaveAttendanceService $leaveAttendanceService)
    {
        $this->leaveRequestService = $leaveRequestService;
        $this->leavePolicyService = $leavePolicyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->requirePermission('leave-request.view');
        if ($request->ajax()) {
            $query = LeaveRequest::query()->with(['employee', 'leaveType']);

            // Phase D2: an admin linked to an employee only sees their own requests.
            if ($employeeId = $this->selfEmployeeId()) {
                $query->where('employee_id', $employeeId);
            }

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
                ->addColumn('leave_type_name', function ($row) {
                    return $row->leaveType->name ?? '-';
                })
                ->addColumn('duration', function ($row) {
                    $start = $row->start_date ? $row->start_date->format('d-m-Y') : '-';
                    $end = $row->end_date ? $row->end_date->format('d-m-Y') : '-';
                    if ($row->duration_type === 'half_day') {
                        $session = $row->half_day_session === 'second_half' ? '2nd half' : '1st half';
                        return $start . '<br><small>Half day (' . $session . ') — ' . $row->total_days . ' day</small>';
                    }
                    return $start . ' to ' . $end . '<br><small>' . $row->total_days . ' day(s)</small>';
                })
                ->addColumn('approval_badge', function ($row) {
                    $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
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
     * Team leave calendar (Phase F2). Renders a month view of who is on leave.
     */
    public function calendar()
    {
        $this->requirePermission('leave-request.view');
        return view('admin.leave-requests.calendar');
    }

    /**
     * Calendar events feed (AJAX) for the team leave calendar.
     * Shows approved and pending leave; self-service admins see only their own.
     */
    public function calendarEvents(Request $request)
    {
        $this->requirePermission('leave-request.view');
        $query = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->whereIn('approval_status', ['approved', 'pending']);

        // Phase D2: an admin linked to an employee only sees their own leave.
        if ($employeeId = $this->selfEmployeeId()) {
            $query->where('employee_id', $employeeId);
        }

        // Constrain to the range FullCalendar requests, when provided.
        if ($request->filled('start') && $request->filled('end')) {
            $query->where('start_date', '<=', $request->input('end'))
                  ->where('end_date', '>=', $request->input('start'));
        }

        $events = $query->get()->map(function ($leave) {
            $approved = $leave->approval_status === 'approved';
            $name = $leave->employee->full_name ?? ('#' . $leave->employee_id);
            $type = $leave->leaveType->name ?? 'Leave';

            return [
                'id' => $leave->id,
                'title' => $name . ' — ' . $type,
                'start' => $leave->start_date->format('Y-m-d'),
                // FullCalendar treats all-day end as exclusive, so add a day.
                'end' => $leave->end_date->copy()->addDay()->format('Y-m-d'),
                'allDay' => true,
                'color' => $approved ? '#16a34a' : '#f59e0b',
                'extendedProps' => [
                    'status' => $leave->approval_status,
                    'duration' => $leave->duration_type === 'half_day'
                        ? ('Half day (' . ($leave->half_day_session ?? '') . ')')
                        : ($leave->total_days . ' day(s)'),
                    'description' => $name . ' — ' . $type . ' — ' . ucfirst($leave->approval_status),
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->requirePermission('leave-request.create');
        // Self-service admins may only file for themselves.
        $employees = ($selfId = $this->selfEmployeeId())
            ? Employee::where('id', $selfId)->get()
            : Employee::active()->get();
        $leaveTypes = LeaveType::active()->get();

        return view('admin.leave-requests.create', compact('employees', 'leaveTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeaveRequestRequest $request)
    {
        $this->requirePermission('leave-request.create');
        DB::beginTransaction();

        try {
            $data = $request->validated();

            // Self-service admins can only file for themselves.
            if ($selfId = $this->selfEmployeeId()) {
                $data['employee_id'] = $selfId;
            }

            // Handle supporting document upload (Phase D4).
            $hasAttachment = $request->hasFile('attachment');
            if ($hasAttachment) {
                $data['attachment'] = Images::upload('leave-requests', $request->file('attachment'));
            }

            // Phase B/D: enforce leave-type policy (eligibility + request rules + half-day).
            $policyErrors = $this->policyErrors($data, $hasAttachment);
            if (!empty($policyErrors)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'This request violates the leave type policy.',
                    'errors' => ['policy' => $policyErrors],
                ], 422);
            }

            $leaveRequest = $this->leaveRequestService->create($data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-requests',
                'action' => 'create',
                'model' => 'LeaveRequest',
                'model_id' => $leaveRequest->id,
                'description' => 'Leave request submitted — ' . $this->leaveSummary($leaveRequest),
                'new_data' => $leaveRequest->toArray(),
                'old_data' => null
            ]);

            // If an active Workflow Engine definition exists for LeaveRequest, kick off
            // an approval instance. Otherwise notify reviewers on the direct path.
            if (WorkflowDefinition::where('approvable_type', LeaveRequest::class)->where('status', true)->exists()) {
                app(WorkflowEngineService::class)->start($leaveRequest, auth()->guard('admin')->id());
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave request created successfully.',
                'warning' => $this->overlapWarning($leaveRequest),
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
        $this->requirePermission('leave-request.edit');
        $employees = Employee::active()->get();
        $leaveTypes = LeaveType::active()->get();

        return view('admin.leave-requests.edit', compact('leaveRequest', 'employees', 'leaveTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeaveRequestRequest $request, LeaveRequest $leaveRequest)
    {
        $this->requirePermission('leave-request.edit');
        DB::beginTransaction();

        try {
            $oldData = $leaveRequest->toArray();
            $wasApproved = $leaveRequest->approval_status === 'approved';
            $data = $request->validated();

            // Handle supporting document upload (Phase D4). Keep existing on no new file.
            $hasNewAttachment = $request->hasFile('attachment');
            if ($hasNewAttachment) {
                $data['attachment'] = Images::update('leave-requests', $leaveRequest->attachment, $request->file('attachment'));
            }
            $hasAttachment = $hasNewAttachment || !empty($leaveRequest->attachment);

            // Phase B/D: enforce leave-type policy (eligibility + request rules + half-day).
            $policyErrors = $this->policyErrors($data, $hasAttachment);
            if (!empty($policyErrors)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'This request violates the leave type policy.',
                    'errors' => ['policy' => $policyErrors],
                ], 422);
            }

            if ($wasApproved) {
                $this->leaveAttendanceService->removeLeave($leaveRequest);
            }
            $updatedLeaveRequest = $this->leaveRequestService->update($leaveRequest, $data);
            if ($wasApproved) {
                $this->leaveAttendanceService->syncApprovedLeave($updatedLeaveRequest);
            }

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
                'message' => 'Leave request updated successfully.',
                'warning' => $this->overlapWarning($updatedLeaveRequest),
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
        $this->requirePermission('leave-request.delete');
        DB::beginTransaction();

        try {
            $oldData = $leaveRequest->toArray();

            if ($leaveRequest->approval_status === 'approved') {
                $this->leaveAttendanceService->removeLeave($leaveRequest);
            }

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
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $this->requirePermission('leave-request.approve');
        // Self-service admins cannot approve leave (their own or anyone's).
        if ($this->selfEmployeeId()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to approve leave requests.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $oldData = $leaveRequest->toArray();

            // Message adjusts when a multi-level workflow only advances a step.
            $resultMessage = 'Leave request approved.';
            $approvedDirectly = false;

            // Fallback-safe: only route through the Workflow Engine when an active
            // WorkflowDefinition exists for LeaveRequest. Today none exists, so this
            // always takes the else branch — the exact original behavior.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', LeaveRequest::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $leaveRequest->workflowInstance;

                if ($instance) {
                    $instance = app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'approved');

                    // Multi-level: a 'pending' result means the step advanced but the
                    // request is not yet fully approved (balance not deducted yet).
                    if ($instance->current_status === 'pending') {
                        $resultMessage = 'Approved at this level. Forwarded to the next approver.';
                    }
                } else {
                    $this->leaveRequestService->approve($leaveRequest);
                    $approvedDirectly = true;
                }
            } else {
                // Direct path: guard against insufficient balance. The admin may
                // proceed anyway by re-sending with override=1 (warn + allow override).
                $override = $request->boolean('override');

                if (!$override && !$this->leaveRequestService->hasSufficientBalance($leaveRequest)) {
                    DB::rollBack();

                    $remaining = $this->leaveRequestService->remainingBalance($leaveRequest);

                    return response()->json([
                        'status' => false,
                        'requires_override' => true,
                        'message' => 'Insufficient leave balance: ' . number_format($remaining, 2)
                            . ' day(s) remaining but ' . number_format((float) $leaveRequest->total_days, 2)
                            . ' day(s) requested. Approve anyway?',
                    ]);
                }

                $this->leaveRequestService->approve($leaveRequest, $override);
                $approvedDirectly = true;
            }

            if ($approvedDirectly && $leaveRequest->fresh()->approval_status === 'approved') {
                $this->leaveAttendanceService->syncApprovedLeave($leaveRequest->fresh());
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-requests',
                'action' => 'approve',
                'model' => 'LeaveRequest',
                'model_id' => $leaveRequest->id,
                'description' => 'Leave request approved — ' . $this->leaveSummary($leaveRequest),
                'new_data' => $leaveRequest->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => $resultMessage
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
        $this->requirePermission('leave-request.approve');
        // Self-service admins cannot reject leave.
        if ($this->selfEmployeeId()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to reject leave requests.',
            ], 403);
        }

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
                'description' => 'Leave request rejected — ' . $this->leaveSummary($leaveRequest),
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
     * Cancel the request. Refunds balance if it was approved (Phase D3).
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest)
    {
        $this->requirePermission('leave-request.cancel');
        if (in_array($leaveRequest->approval_status, ['rejected', 'cancelled'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'This request cannot be cancelled.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $oldData = $leaveRequest->toArray();

            $this->leaveRequestService->cancel($leaveRequest, $request->input('cancellation_reason'));
            $this->leaveAttendanceService->removeLeave($leaveRequest);

            // A cancelled pending request must never remain actionable in Workflow.
            $leaveRequest->workflowInstances()
                ->where('current_status', 'pending')
                ->update(['current_status' => 'cancelled', 'completed_at' => now()]);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-requests',
                'action' => 'cancel',
                'model' => 'LeaveRequest',
                'model_id' => $leaveRequest->id,
                'description' => 'Leave request cancelled — ' . $this->leaveSummary($leaveRequest),
                'new_data' => $leaveRequest->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave request cancelled.'
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
     * The employee id the current admin is tied to, or null for a general/super
     * admin (no employee_id). Drives Phase D2 self-service scoping.
     */
    protected function selfEmployeeId(): ?int
    {
        $admin = auth()->guard('admin')->user();

        return $admin && $admin->employee_id ? (int) $admin->employee_id : null;
    }

    protected function requirePermission(string $permission): void
    {
        $admin = auth()->guard('admin')->user();
        abort_unless($admin && $admin->can($permission), 403);
    }

    /**
     * Collect hard policy violations (eligibility + request rules) for a submission.
     * Returns an array of human-readable error strings; empty means the request is allowed.
     */
    protected function policyErrors(array $data, bool $hasAttachment = false): array
    {
        $employee = Employee::find($data['employee_id'] ?? null);
        $leaveType = LeaveType::find($data['leave_type_id'] ?? null);

        if (!$employee || !$leaveType) {
            return [];
        }

        $isHalfDay = ($data['duration_type'] ?? 'full_day') === 'half_day';
        $totalDays = $isHalfDay ? 0.5 : $this->leaveRequestService->workingDays($data['start_date'], $data['end_date']);

        $errors = array_merge(
            $this->leavePolicyService->eligibilityErrors($employee, $leaveType),
            $this->leavePolicyService->requestRuleErrors($leaveType, $data['start_date'], $totalDays, $hasAttachment)
        );

        // Half-day permitted only when the leave type allows it (Phase D1).
        if ($isHalfDay && !$leaveType->allow_half_day) {
            $errors[] = 'This leave type does not allow half-day leave.';
        }

        return $errors;
    }

    /**
     * Build a human-readable overlap warning for a saved request, or null when
     * there is no overlap. Overlaps are surfaced as a warning only (never blocked).
     */
    protected function overlapWarning(LeaveRequest $leaveRequest): ?string
    {
        $overlaps = $this->leaveRequestService->overlappingRequests($leaveRequest);

        if ($overlaps->isEmpty()) {
            return null;
        }

        $ranges = $overlaps->map(function ($r) {
            return $r->start_date->format('d-m-Y') . ' to ' . $r->end_date->format('d-m-Y')
                . ' (' . ucfirst($r->approval_status) . ')';
        })->implode(', ');

        return 'Heads up: this overlaps ' . $overlaps->count()
            . ' existing request(s) for this employee — ' . $ranges . '.';
    }

    /**
     * Human-friendly one-line summary of a leave request, used in activity-log
     * descriptions. The ActivityLog `created` hook turns each log into an in-app
     * notification, so this keeps those notifications readable (Phase F1).
     */
    protected function leaveSummary(LeaveRequest $leaveRequest): string
    {
        $name = $leaveRequest->employee->full_name ?? ('employee #' . $leaveRequest->employee_id);
        $type = $leaveRequest->leaveType->name ?? 'leave';
        $period = optional($leaveRequest->start_date)->format('d-m-Y') . ' to ' . optional($leaveRequest->end_date)->format('d-m-Y');

        return $name . ' (' . $type . ', ' . $period . ')';
    }

    /**
     * Update status (AJAX switch toggle)
     */
    public function updateStatus(Request $request, int $id)
    {
        $this->requirePermission('leave-request.edit');
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
