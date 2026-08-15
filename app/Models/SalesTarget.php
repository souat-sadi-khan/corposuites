<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTarget extends Model
{
    use HasFactory;

    public const PERIOD_TYPES = ['monthly', 'quarterly', 'yearly'];

    protected $fillable = [
        'admin_id', 'period_type', 'period_start', 'period_end', 'target_amount', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'period_start' => 'date',
        'period_end' => 'date',
        'target_amount' => 'decimal:2',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Sum of grand_total from this salesperson's non-cancelled Sales Orders
     * falling within the target period. Computed live, not stored — there is
     * no reliable way to keep a stored figure in sync as orders are edited
     * after the fact, so this is always derived fresh from current data.
     */
    public function getAchievedAmountAttribute(): float
    {
        return (float) SalesOrder::where('assigned_to', $this->admin_id)
            ->where('order_status', '!=', 'cancelled')
            ->whereBetween('order_date', [$this->period_start, $this->period_end])
            ->sum('grand_total');
    }
}
