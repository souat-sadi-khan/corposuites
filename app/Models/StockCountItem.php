<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_count_id', 'product_id', 'system_quantity', 'counted_quantity', 'notes'
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'counted_quantity' => 'decimal:2',
    ];

    protected $appends = ['variance'];

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Difference between what was physically counted and the recorded system
     * quantity. Null when no system quantity was entered — there is nothing
     * to compare against (no live stock balance exists yet in this project).
     */
    public function getVarianceAttribute(): ?float
    {
        if (is_null($this->system_quantity)) {
            return null;
        }

        return round((float) $this->counted_quantity - (float) $this->system_quantity, 2);
    }
}
