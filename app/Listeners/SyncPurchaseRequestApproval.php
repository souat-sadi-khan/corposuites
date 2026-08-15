<?php

namespace App\Listeners;

use App\Events\WorkflowApproved;
use App\Events\WorkflowRejected;
use App\Events\WorkflowResubmitted;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;

class SyncPurchaseRequestApproval
{
    public function __construct(protected PurchaseRequestService $purchaseRequestService)
    {
    }

    public function handleApproved(WorkflowApproved $event): void
    {
        if ($event->instance->approvable_type !== PurchaseRequest::class) {
            return;
        }

        $purchaseRequest = PurchaseRequest::find($event->instance->approvable_id);

        if ($purchaseRequest) {
            $this->purchaseRequestService->approve($purchaseRequest);
        }
    }

    public function handleRejected(WorkflowRejected $event): void
    {
        if ($event->instance->approvable_type !== PurchaseRequest::class) {
            return;
        }

        $purchaseRequest = PurchaseRequest::find($event->instance->approvable_id);

        if ($purchaseRequest) {
            $this->purchaseRequestService->reject($purchaseRequest);
        }
    }

    /**
     * On resubmission the module's own request_status is simply reset to 'pending'
     * directly (not routed through the service, since there is no dedicated
     * "resubmit" side effect for Purchase Requests) — same pattern as
     * SyncLeaveRequestApproval::handleResubmitted().
     */
    public function handleResubmitted(WorkflowResubmitted $event): void
    {
        if ($event->instance->approvable_type !== PurchaseRequest::class) {
            return;
        }

        PurchaseRequest::where('id', $event->instance->approvable_id)
            ->update(['request_status' => 'pending']);
    }
}
