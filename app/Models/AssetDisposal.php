<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    protected $table = 'asset_disposals';

    public const METHODS = ['sold', 'scrapped', 'donated', 'written_off', 'traded_in', 'lost'];

    public const STATUSES = ['pending', 'completed', 'cancelled'];

    protected $fillable = [
        'asset_id',
        'disposal_date',
        'disposal_method',
        'recipient',
        'proceeds',
        'book_value_at_disposal',
        'gain_loss',
        'disposal_status',
        'approved_by',
        'reason',
        'notes',
        'status',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'proceeds' => 'decimal:2',
        'book_value_at_disposal' => 'decimal:2',
        'gain_loss' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Only a completed disposal has actually removed the asset from use.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->disposal_status === 'completed';
    }

    public function getIsGainAttribute(): bool
    {
        return (float) $this->gain_loss > 0;
    }

    public function getMethodLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->disposal_method));
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
