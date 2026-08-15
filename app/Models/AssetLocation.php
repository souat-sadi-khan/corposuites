<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetLocation extends Model
{
    protected $table = 'asset_locations';

    public const LOCATION_TYPES = ['office', 'branch', 'warehouse', 'site', 'other'];

    protected $fillable = [
        'name',
        'code',
        'location_type',
        'department_id',
        'building',
        'floor',
        'room',
        'address',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Building / floor / room folded into one readable line, omitting the
     * parts that were not recorded.
     */
    public function getPlacementAttribute(): ?string
    {
        $parts = array_filter([
            $this->building ? 'Bldg ' . $this->building : null,
            $this->floor ? 'Floor ' . $this->floor : null,
            $this->room ? 'Room ' . $this->room : null,
        ]);

        return $parts ? implode(' · ', $parts) : null;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetLocationMovement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
