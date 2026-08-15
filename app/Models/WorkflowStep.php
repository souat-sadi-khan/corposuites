<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id', 'workflow_template_id', 'step_order', 'name', 'approval_type'
    ];

    // Exactly one of workflow_definition_id / workflow_template_id must be set:
    // a step either belongs to a live WorkflowDefinition or to a reusable WorkflowTemplate
    // being edited in the builder. Enforced in the service layer when steps are created.

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(WorkflowStepApprover::class);
    }
}
