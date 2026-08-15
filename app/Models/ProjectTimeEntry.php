<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectTimeEntry extends Model
{
    protected $table = 'project_time_entries';

    protected $fillable = [
        'employee_id',
        'project_id',
        'project_task_id',
        'project_timesheet_id',
        'work_date',
        'started_at',
        'ended_at',
        'hours',
        'is_billable',
        'description',
        'status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'hours' => 'decimal:2',
        'is_billable' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * A timer that was started but never stopped.
     */
    public function getIsRunningAttribute(): bool
    {
        return (bool) $this->started_at && ! $this->ended_at;
    }

    /**
     * How long a still-running timer has been going — a live figure, so it
     * is not cached anywhere and is only ever read at render time.
     */
    public function getElapsedMinutesAttribute(): ?int
    {
        if (! $this->is_running) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes(now());
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function projectTimesheet(): BelongsTo
    {
        return $this->belongsTo(ProjectTimesheet::class, 'project_timesheet_id');
    }

    /**
     * The Project Billing line this entry was billed on, if any — the
     * inverse of ProjectInvoiceItem::timeEntry(). No FK column of its own
     * lives on this table; "billed" is expressed entirely through whether
     * such an item exists on a still-active invoice (see is_locked below).
     */
    public function invoiceItem(): HasOne
    {
        return $this->hasOne(ProjectInvoiceItem::class, 'project_time_entry_id');
    }

    /**
     * Locked once either its timesheet has been submitted for approval, or
     * it has been billed on a Project Invoice that hasn't been cancelled —
     * editing or deleting it after either would let the underlying hours
     * drift away from what was actually reviewed or billed. Enforced by
     * ProjectTimeEntryService, not just hidden in the UI. Cancelling the
     * invoice is what frees a billed entry back up — no separate unlink
     * step is needed anywhere, since this accessor is the only thing that
     * ever calls an entry "billed".
     */
    public function getIsLockedAttribute(): bool
    {
        $timesheetLocked = $this->projectTimesheet
            && in_array($this->projectTimesheet->timesheet_status, ProjectTimesheet::LOCKED_STATUSES, true);

        $invoiceLocked = $this->invoiceItem
            && $this->invoiceItem->projectInvoice
            && $this->invoiceItem->projectInvoice->invoice_status !== 'cancelled';

        return (bool) ($timesheetLocked || $invoiceLocked);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * A timer that is currently ticking (started, not yet stopped).
     */
    public function scopeRunning($query)
    {
        return $query->whereNotNull('started_at')->whereNull('ended_at');
    }
}
