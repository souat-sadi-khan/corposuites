<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningStockItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'opening_stock_id', 'product_id', 'quantity', 'unit_cost', 'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function openingStock(): BelongsTo
    {
        return $this->belongsTo(OpeningStock::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
