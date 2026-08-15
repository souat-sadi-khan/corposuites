<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'in_transit', 'completed', 'cancelled'];

    protected $fillable = [
        'transfer_number', 'from_warehouse_id', 'to_warehouse_id', 'from_warehouse_location_id',
        'to_warehouse_location_id', 'transfer_date', 'reason', 'transfer_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'transfer_date' => 'date',
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function fromWarehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'from_warehouse_location_id');
    }

    public function toWarehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'to_warehouse_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
