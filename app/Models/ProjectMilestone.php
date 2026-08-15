<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    protected $table = 'project_milestones';

    public const STATUSES = ['pending', 'in_progress', 'completed', 'delayed', 'cancelled'];

    /**
     * A milestone that is done (or abandoned) can no longer run late —
     * shared by the accessors below and the controller's overdue filter so
     * the two can never disagree.
     */
    public const CLOSED_STATUSES = ['completed', 'cancelled'];

    protected $fillable = [
        'project_id',
        'name',
        'sort_order',
        'due_date',
        'completed_date',
        'milestone_status',
        'assigned_to',
        'deliverables',
        'notes',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'due_date' => 'date',
        'completed_date' => 'date',
        'status' => 'boolean',
    ];

    public function getMilestoneStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->milestone_status));
    }

    /**
     * Past its due date and still open. Computed rather than stored: a stored
     * flag would need a scheduled job to become true as the date passes
     * (same reasoning as Project::is_overdue).
     */
    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->milestone_status, self::CLOSED_STATUSES, true)) {
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
        if (in_array($this->milestone_status, self::CLOSED_STATUSES, true)) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false);
    }

    /**
     * Whether a completed milestone landed on or before its due date.
     * Null while it is still open — there is nothing to judge yet.
     */
    public function getWasOnTimeAttribute(): ?bool
    {
        if ($this->milestone_status !== 'completed' || ! $this->completed_date) {
            return null;
        }

        return $this->completed_date->lessThanOrEqualTo($this->due_date);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
