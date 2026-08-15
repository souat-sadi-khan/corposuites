<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $table = 'projects';

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public const STATUSES = ['planned', 'in_progress', 'on_hold', 'completed', 'cancelled'];

    /**
     * A project is finished (or abandoned) once it reaches one of these —
     * used by is_overdue, since neither can be late any more.
     */
    public const CLOSED_STATUSES = ['completed', 'cancelled'];

    protected $fillable = [
        'project_code',
        'name',
        'client_id',
        'department_id',
        'project_manager_id',
        'start_date',
        'end_date',
        'actual_end_date',
        'priority',
        'project_status',
        'progress_percent',
        'description',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
        'progress_percent' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Human-readable status (the enum stores snake_case).
     */
    public function getProjectStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->project_status));
    }

    public function getPriorityLabelAttribute(): string
    {
        return ucfirst($this->priority);
    }

    /**
     * Past its planned end date and still open. Computed rather than stored:
     * a stored flag would need a scheduled job to become true as the date
     * passes (same reasoning as AssetAssignment::is_overdue).
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->end_date || in_array($this->project_status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        return $this->end_date->isPast();
    }

    /**
     * Signed: negative once overdue, so one value carries both
     * "how long left" and "how late".
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->end_date || in_array($this->project_status, self::CLOSED_STATUSES, true)) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->end_date->copy()->startOfDay(), false);
    }

    /**
     * Planned length in days, inclusive of both ends. Null when open-ended.
     */
    public function getDurationDaysAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
