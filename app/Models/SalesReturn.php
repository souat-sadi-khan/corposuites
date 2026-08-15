<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'received', 'inspected', 'completed', 'cancelled'];

    protected $fillable = [
        'return_number', 'customer_id', 'sales_order_id', 'delivery_id', 'return_date', 'reason',
        'return_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'return_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
