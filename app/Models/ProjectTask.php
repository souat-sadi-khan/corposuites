<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    protected $table = 'project_tasks';

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public const STATUSES = ['todo', 'in_progress', 'review', 'done', 'cancelled'];

    /**
     * Finished or abandoned work can no longer run late — shared by the
     * accessors below and the controller's overdue filter so the two can
     * never disagree.
     */
    public const CLOSED_STATUSES = ['done', 'cancelled'];

    protected $fillable = [
        'task_code',
        'project_id',
        'project_milestone_id',
        'title',
        'assigned_to',
        'priority',
        'task_status',
        'start_date',
        'due_date',
        'completed_date',
        'estimated_hours',
        'progress_percent',
        'sort_order',
        'description',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'progress_percent' => 'integer',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public function getTaskStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->task_status));
    }

    public function getPriorityLabelAttribute(): string
    {
        return ucfirst($this->priority);
    }

    /**
     * Past its due date and still open. Computed rather than stored: a stored
     * flag would need a scheduled job to become true as the date passes
     * (same reasoning as Project::is_overdue / ProjectMilestone::is_overdue).
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_date || in_array($this->task_status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Signed: negative once overdue, so one value carries both
     * "how long left" and "how late".
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->due_date || in_array($this->task_status, self::CLOSED_STATUSES, true)) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'project_milestone_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Work that is neither done nor abandoned.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('task_status', self::CLOSED_STATUSES);
    }
}
