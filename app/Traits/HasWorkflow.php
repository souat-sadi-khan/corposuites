<?php

namespace App\Traits;

use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasWorkflow
{
    /**
     * The latest workflow instance for this approvable record.
     */
    public function workflowInstance(): MorphOne
    {
        return $this->morphOne(WorkflowInstance::class, 'approvable')->latestOfMany();
    }

    /**
     * Full workflow instance history for this approvable record
     * (includes prior rejected/resubmitted instances).
     */
    public function workflowInstances(): MorphMany
    {
        return $this->morphMany(WorkflowInstance::class, 'approvable');
    }
}
