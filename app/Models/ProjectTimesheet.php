<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTimesheet extends Model
{
    protected $table = 'project_timesheets';

    public const STATUSES = ['draft', 'submitted', 'approved', 'rejected'];

    /**
     * Once submitted, a timesheet's own linked entries are locked (see
     * ProjectTimeEntry::is_locked) — shared here so the "can this be
     * regenerated?" check in the service and the "is this still open?"
     * check in the controller can never disagree.
     */
    public const LOCKED_STATUSES = ['submitted', 'approved'];

    protected $fillable = [
        'employee_id',
        'week_start_date',
        'week_end_date',
        'total_hours',
        'billable_hours',
        'timesheet_status',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
        'status',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'total_hours' => 'decimal:2',
        'billable_hours' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function getTimesheetStatusLabelAttribute(): string
    {
        return ucfirst($this->timesheet_status);
    }

    /**
     * "03 Aug - 09 Aug 2026" — a plain date range reads more naturally than
     * two separate columns for what is, functionally, a single period.
     */
    public function getWeekLabelAttribute(): string
    {
        return $this->week_start_date->format('d M') . ' - ' . $this->week_end_date->format('d M Y');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(ProjectTimeEntry::class, 'project_timesheet_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
