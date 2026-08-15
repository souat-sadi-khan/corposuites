<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
