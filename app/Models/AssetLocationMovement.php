<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLocationMovement extends Model
{
    protected $table = 'asset_location_movements';

    protected $fillable = [
        'asset_id',
        'asset_location_id',
        'moved_date',
        'moved_by',
        'reason',
        'notes',
        'status',
    ];

    protected $casts = [
        'moved_date' => 'date',
        'status' => 'boolean',
    ];

    /**
     * Whether this movement is the asset's current whereabouts — i.e. the
     * most recent one recorded for it. Computed rather than stored as an
     * `is_current` flag, which would have to be flipped across sibling rows
     * on every insert, edit and delete and could silently end up with two
     * "current" rows. The latest row simply is the current one.
     */
    public function getIsCurrentAttribute(): bool
    {
        $latestId = static::where('asset_id', $this->asset_id)
            ->orderBy('moved_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->value('id');

        return (int) $latestId === (int) $this->id;
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assetLocation(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class);
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'moved_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
