<?php

namespace App\Listeners;

use App\Events\WorkflowApproved;
use App\Events\WorkflowRejected;
use App\Events\WorkflowResubmitted;
use App\Models\ExpenseClaim;
use App\Services\ExpenseClaimService;

class SyncExpenseClaimApproval
{
    public function __construct(protected ExpenseClaimService $expenseClaimService)
    {
    }

    public function handleApproved(WorkflowApproved $event): void
    {
        if ($event->instance->approvable_type !== ExpenseClaim::class) {
            return;
        }

        $expenseClaim = ExpenseClaim::find($event->instance->approvable_id);

        if ($expenseClaim) {
            $this->expenseClaimService->approve($expenseClaim);
        }
    }

    public function handleRejected(WorkflowRejected $event): void
    {
        if ($event->instance->approvable_type !== ExpenseClaim::class) {
            return;
        }

        $expenseClaim = ExpenseClaim::find($event->instance->approvable_id);

        if ($expenseClaim) {
            $this->expenseClaimService->reject($expenseClaim);
        }
    }

    /**
     * On resubmission the module's own approval_status is simply reset to 'pending'
     * directly — documented in CLAUDE.md.
     */
    public function handleResubmitted(WorkflowResubmitted $event): void
    {
        if ($event->instance->approvable_type !== ExpenseClaim::class) {
            return;
        }

        ExpenseClaim::where('id', $event->instance->approvable_id)
            ->update(['approval_status' => 'pending']);
    }
}
