<?php

namespace App\Listeners;

use App\Events\WorkflowApproved;
use App\Events\WorkflowRejected;
use App\Events\WorkflowResubmitted;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;

class SyncLeaveRequestApproval
{
    public function __construct(protected LeaveRequestService $leaveRequestService)
    {
    }

    public function handleApproved(WorkflowApproved $event): void
    {
        if ($event->instance->approvable_type !== LeaveRequest::class) {
            return;
        }

        $leaveRequest = LeaveRequest::find($event->instance->approvable_id);

        if ($leaveRequest) {
            $this->leaveRequestService->approve($leaveRequest);
        }
    }

    public function handleRejected(WorkflowRejected $event): void
    {
        if ($event->instance->approvable_type !== LeaveRequest::class) {
            return;
        }

        $leaveRequest = LeaveRequest::find($event->instance->approvable_id);

        if ($leaveRequest) {
            $this->leaveRequestService->reject($leaveRequest);
        }
    }

    /**
     * On resubmission the module's own approval_status is simply reset to 'pending'
     * directly (not routed through the service, since there is no dedicated
     * "resubmit" side effect for Leave Requests) — documented in CLAUDE.md.
     */
    public function handleResubmitted(WorkflowResubmitted $event): void
    {
        if ($event->instance->approvable_type !== LeaveRequest::class) {
            return;
        }

        LeaveRequest::where('id', $event->instance->approvable_id)
            ->update(['approval_status' => 'pending']);
    }
}
