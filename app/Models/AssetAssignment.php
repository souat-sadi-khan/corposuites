<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    protected $table = 'asset_assignments';

    public const STATUSES = ['assigned', 'returned', 'cancelled'];

    public const CONDITIONS = ['new', 'good', 'fair', 'poor'];

    protected $fillable = [
        'asset_id',
        'employee_id',
        'assigned_date',
        'expected_return_date',
        'returned_date',
        'assignment_status',
        'condition_on_assign',
        'condition_on_return',
        'notes',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'expected_return_date' => 'date',
        'returned_date' => 'date',
        'status' => 'boolean',
    ];

    /**
     * Still out with the employee — the state that blocks the asset from
     * being assigned to anyone else.
     */
    public function getIsActiveAssignmentAttribute(): bool
    {
        return $this->assignment_status === 'assigned';
    }

    /**
     * Past its expected return date and not yet returned. Computed rather
     * than stored: a stored flag would need a scheduled job to become true
     * as the date passes, the same reasoning behind `TaxRate::is_current`
     * and `AssetPurchase::is_under_warranty`.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->is_active_assignment
            && $this->expected_return_date !== null
            && $this->expected_return_date->lt(now()->startOfDay());
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
