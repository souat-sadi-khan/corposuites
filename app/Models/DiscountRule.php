<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'discount_type', 'value', 'scope_type',
        'category_id', 'product_id', 'start_date', 'end_date',
        'description', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public const DISCOUNT_TYPES = ['percentage', 'fixed'];
    public const SCOPE_TYPES = ['all', 'category', 'product'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
