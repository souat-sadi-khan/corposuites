<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'warehouse_id', 'batch_number', 'manufacturing_date', 'expiry_date',
        'quantity', 'unit_cost', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
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

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now()->toDateString());
    }
}
