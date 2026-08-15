<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Delivery extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'in_transit', 'delivered', 'cancelled'];

    protected $fillable = [
        'delivery_number', 'sales_order_id', 'delivery_date', 'carrier', 'tracking_number',
        'delivery_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'delivery_date' => 'date',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function deliveryNote(): HasOne
    {
        return $this->hasOne(DeliveryNote::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
