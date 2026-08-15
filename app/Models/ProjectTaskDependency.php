<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTaskDependency extends Model
{
    protected $table = 'project_task_dependencies';

    public const DEPENDENCY_TYPES = ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'];

    protected $fillable = [
        'task_id',
        'depends_on_task_id',
        'dependency_type',
        'lag_days',
        'notes',
        'status',
    ];

    protected $casts = [
        'lag_days' => 'integer',
        'status' => 'boolean',
    ];

    public function getDependencyTypeLabelAttribute(): string
    {
        $map = [
            'finish_to_start' => 'Finish to Start',
            'start_to_start' => 'Start to Start',
            'finish_to_finish' => 'Finish to Finish',
            'start_to_finish' => 'Start to Finish',
        ];

        return $map[$this->dependency_type] ?? ucfirst($this->dependency_type);
    }

    /**
     * The dependent (successor) task — it must wait on the predecessor.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    /**
     * The predecessor task this dependency is on.
     */
    public function dependsOnTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'depends_on_task_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
