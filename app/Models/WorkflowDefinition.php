<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_key', 'name', 'approvable_type', 'approval_mode',
        'workflow_template_id', 'status', 'created_by'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(WorkflowStatus::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function notificationTriggers(): HasMany
    {
        return $this->hasMany(WorkflowNotificationTrigger::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
