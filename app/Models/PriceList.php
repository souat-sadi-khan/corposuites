<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'customer_group_id', 'description', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
