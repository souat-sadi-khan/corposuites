<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $table = 'asset_categories';

    public const DEPRECIATION_METHODS = ['none', 'straight_line', 'reducing_balance'];

    protected $fillable = [
        'name',
        'code',
        'depreciation_method',
        'useful_life_years',
        'salvage_value_percent',
        'description',
        'status',
    ];

    protected $casts = [
        'useful_life_years' => 'integer',
        'salvage_value_percent' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Human-readable depreciation method (the enum stores snake_case).
     */
    public function getDepreciationMethodLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->depreciation_method));
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
