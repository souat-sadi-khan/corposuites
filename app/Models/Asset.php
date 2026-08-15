<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $table = 'assets';

    public const CONDITIONS = ['new', 'good', 'fair', 'poor'];

    public const STATUSES = ['in_store', 'in_use', 'under_maintenance', 'disposed'];

    protected $fillable = [
        'asset_code',
        'name',
        'asset_category_id',
        'serial_number',
        'model_number',
        'manufacturer',
        'condition',
        'asset_status',
        'description',
        'notes',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Human-readable lifecycle state (the enum stores snake_case).
     */
    public function getAssetStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->asset_status));
    }

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }

    /**
     * One purchase record per asset. Added because the Asset Purchase
     * Information screen genuinely needs it (to exclude assets that
     * already have one from its picker), not speculatively.
     */
    public function assetPurchase(): HasOne
    {
        return $this->hasOne(AssetPurchase::class);
    }

    /**
     * One disposal record per asset. Added because the Disposal
     * Management picker needs to exclude already-disposed assets.
     */
    public function assetDisposal(): HasOne
    {
        return $this->hasOne(AssetDisposal::class);
    }

    /**
     * Assignment history. Added because the Asset Assignment picker needs
     * to exclude assets currently out with someone, not speculatively.
     */
    public function assetAssignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    /**
     * Physical location history, newest first.
     */
    public function locationMovements(): HasMany
    {
        return $this->hasMany(AssetLocationMovement::class);
    }

    /**
     * Where the asset is now — the most recently recorded movement.
     */
    public function currentLocationMovement(): HasOne
    {
        // Tie-broken by id, so two movements recorded on the same date
        // still resolve to a single, deterministic "current" row.
        return $this->hasOne(AssetLocationMovement::class)->latestOfMany(['moved_date', 'id']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
