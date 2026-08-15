<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'warehouse_id', 'reorder_level', 'reorder_quantity', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'reorder_level' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
