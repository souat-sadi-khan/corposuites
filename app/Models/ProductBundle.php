<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku', 'name', 'description', 'price', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
