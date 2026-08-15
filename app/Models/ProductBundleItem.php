<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_bundle_id', 'product_id', 'quantity'
    ];

    public function productBundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
