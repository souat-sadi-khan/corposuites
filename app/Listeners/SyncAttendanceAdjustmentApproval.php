<?php

namespace App\Listeners;

use App\Events\WorkflowApproved;
use App\Events\WorkflowRejected;
use App\Events\WorkflowResubmitted;
use App\Models\AttendanceAdjustment;
use App\Services\AttendanceAdjustmentService;

class SyncAttendanceAdjustmentApproval
{
    public function __construct(protected AttendanceAdjustmentService $attendanceAdjustmentService)
    {
    }

    public function handleApproved(WorkflowApproved $event): void
    {
        if ($event->instance->approvable_type !== AttendanceAdjustment::class) {
            return;
        }

        $attendanceAdjustment = AttendanceAdjustment::find($event->instance->approvable_id);

        if ($attendanceAdjustment) {
            $this->attendanceAdjustmentService->approve($attendanceAdjustment);
        }
    }

    public function handleRejected(WorkflowRejected $event): void
    {
        if ($event->instance->approvable_type !== AttendanceAdjustment::class) {
            return;
        }

        $attendanceAdjustment = AttendanceAdjustment::find($event->instance->approvable_id);

        if ($attendanceAdjustment) {
            $this->attendanceAdjustmentService->reject($attendanceAdjustment);
        }
    }

    /**
     * On resubmission the module's own approval_status is simply reset to 'pending'
     * directly — documented in CLAUDE.md.
     */
    public function handleResubmitted(WorkflowResubmitted $event): void
    {
        if ($event->instance->approvable_type !== AttendanceAdjustment::class) {
            return;
        }

        AttendanceAdjustment::where('id', $event->instance->approvable_id)
            ->update(['approval_status' => 'pending']);
    }
}
