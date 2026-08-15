<?php

namespace App\Events;

use App\Models\WorkflowInstance;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when an intermediate step is approved and the instance moves to the
 * next step (sequential workflows), but the instance has not yet fully
 * completed. Not consumed by the HRM sync listeners (they only care about
 * final Approved/Rejected/Resubmitted) — kept purely for the notification
 * bridge ('step_pending' trigger event) and any future consumers.
 */
class WorkflowStepAdvanced
{
    use Dispatchable;

    public function __construct(public readonly WorkflowInstance $instance)
    {
    }
}
