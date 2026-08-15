<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowNotificationTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id', 'event', 'notify_type', 'notify_id', 'template_message', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }
}
