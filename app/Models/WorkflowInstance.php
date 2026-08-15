<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id', 'approvable_type', 'approvable_id', 'current_step_id',
        'current_status', 'initiated_by', 'resubmission_of', 'resubmission_count', 'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'initiated_by');
    }

    public function resubmissionOf(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'resubmission_of');
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowInstanceApproval::class);
    }
}
