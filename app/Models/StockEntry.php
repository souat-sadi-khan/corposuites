<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockEntry extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'posted', 'cancelled'];

    protected $fillable = [
        'entry_number', 'warehouse_id', 'warehouse_location_id', 'goods_receipt_id', 'entry_date', 'reason',
        'entry_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'entry_date' => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockEntryItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
