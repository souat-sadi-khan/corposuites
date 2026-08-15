<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCommission extends Model
{
    use HasFactory;

    public const PERIOD_TYPES = ['monthly', 'quarterly', 'yearly'];
    public const PAYMENT_STATUSES = ['pending', 'paid'];

    protected $fillable = [
        'admin_id', 'period_type', 'period_start', 'period_end', 'commission_rate',
        'sales_amount', 'commission_amount', 'payment_status', 'payment_date', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'commission_rate' => 'decimal:2',
        'sales_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
