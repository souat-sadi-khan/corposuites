<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceSchedule extends Model
{
    protected $table = 'asset_maintenance_schedules';

    public const MAINTENANCE_TYPES = ['preventive', 'inspection', 'calibration', 'servicing', 'other'];

    public const FREQUENCIES = ['one_time', 'weekly', 'monthly', 'quarterly', 'half_yearly', 'yearly'];

    public const STATUSES = ['active', 'paused', 'completed', 'cancelled'];

    protected $fillable = [
        'asset_id',
        'title',
        'maintenance_type',
        'frequency',
        'start_date',
        'last_performed_date',
        'next_due_date',
        'vendor_id',
        'assigned_to',
        'estimated_cost',
        'schedule_status',
        'instructions',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'last_performed_date' => 'date',
        'next_due_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Human-readable frequency (the enum stores snake_case).
     */
    public function getFrequencyLabelAttribute(): string
    {
        return $this->frequency === 'one_time'
            ? 'One Time'
            : ucwords(str_replace('_', ' ', $this->frequency));
    }

    /**
     * Only an active schedule can fall due — a paused, completed or
     * cancelled one is not waiting on anybody.
     */
    public function getIsDueAttribute(): bool
    {
        return $this->schedule_status === 'active'
            && $this->next_due_date !== null
            && $this->next_due_date->lte(now()->startOfDay());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->schedule_status === 'active'
            && $this->next_due_date !== null
            && $this->next_due_date->lt(now()->startOfDay());
    }

    /**
     * Negative once overdue, so the sign carries the meaning. Null when
     * there is no due date to count towards.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if ($this->next_due_date === null) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->next_due_date, false);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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
