<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id', 'key', 'label', 'color', 'is_terminal', 'sort_order'
    ];

    protected $casts = [
        'is_terminal' => 'boolean',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }
}
