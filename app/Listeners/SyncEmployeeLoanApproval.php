<?php

namespace App\Listeners;

use App\Events\WorkflowApproved;
use App\Events\WorkflowRejected;
use App\Events\WorkflowResubmitted;
use App\Models\EmployeeLoan;
use App\Services\EmployeeLoanService;

class SyncEmployeeLoanApproval
{
    public function __construct(protected EmployeeLoanService $employeeLoanService)
    {
    }

    public function handleApproved(WorkflowApproved $event): void
    {
        if ($event->instance->approvable_type !== EmployeeLoan::class) {
            return;
        }

        $employeeLoan = EmployeeLoan::find($event->instance->approvable_id);

        if ($employeeLoan) {
            $this->employeeLoanService->approve($employeeLoan);
        }
    }

    public function handleRejected(WorkflowRejected $event): void
    {
        if ($event->instance->approvable_type !== EmployeeLoan::class) {
            return;
        }

        $employeeLoan = EmployeeLoan::find($event->instance->approvable_id);

        if ($employeeLoan) {
            $this->employeeLoanService->reject($employeeLoan);
        }
    }

    /**
     * On resubmission the module's own approval_status is simply reset to 'pending'
     * directly — documented in CLAUDE.md.
     */
    public function handleResubmitted(WorkflowResubmitted $event): void
    {
        if ($event->instance->approvable_type !== EmployeeLoan::class) {
            return;
        }

        EmployeeLoan::where('id', $event->instance->approvable_id)
            ->update(['approval_status' => 'pending']);
    }
}
