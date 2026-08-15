<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceRecord extends Model
{
    protected $table = 'asset_maintenance_records';

    public const MAINTENANCE_TYPES = ['preventive', 'inspection', 'calibration', 'servicing', 'repair', 'other'];

    public const STATUSES = ['in_progress', 'completed', 'cancelled'];

    protected $fillable = [
        'asset_id',
        'asset_maintenance_schedule_id',
        'title',
        'maintenance_type',
        'performed_date',
        'vendor_id',
        'performed_by',
        'cost',
        'downtime_hours',
        'record_status',
        'work_done',
        'findings',
        'notes',
        'status',
    ];

    protected $casts = [
        'performed_date' => 'date',
        'cost' => 'decimal:2',
        'downtime_hours' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Only a completed job counts as work actually done — an in-progress or
     * cancelled record must not move a schedule's next due date.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->record_status === 'completed';
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->record_status));
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AssetMaintenanceSchedule::class, 'asset_maintenance_schedule_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'performed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
