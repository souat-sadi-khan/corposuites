<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSerial extends Model
{
    use HasFactory;

    public const STATUSES = ['in_stock', 'sold', 'defective', 'returned'];

    protected $fillable = [
        'product_id', 'warehouse_id', 'serial_number', 'serial_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
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
