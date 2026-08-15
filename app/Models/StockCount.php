<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'completed', 'cancelled'];

    protected $fillable = [
        'count_number', 'warehouse_id', 'warehouse_location_id', 'count_date', 'count_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'count_date' => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
