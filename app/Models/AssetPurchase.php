<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetPurchase extends Model
{
    protected $table = 'asset_purchases';

    protected $fillable = [
        'asset_id',
        'vendor_id',
        'purchase_order_id',
        'invoice_number',
        'purchase_date',
        'purchase_cost',
        'additional_cost',
        'warranty_expiry',
        'notes',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_cost' => 'decimal:2',
        'additional_cost' => 'decimal:2',
        'status' => 'boolean',
    ];

    protected $appends = ['total_cost'];

    /**
     * Capitalised cost of the asset — the price paid plus any freight,
     * installation or setup charges. Computed rather than stored, for the
     * same reason every other derived figure in this project is an
     * accessor: a stored total would need re-writing whenever either
     * component changes, giving two values that can disagree.
     */
    public function getTotalCostAttribute(): float
    {
        return round((float) $this->purchase_cost + (float) $this->additional_cost, 2);
    }

    /**
     * Whether the asset is still under warranty today. An asset with no
     * recorded expiry has no warranty to be under, so this is false
     * rather than an open-ended true.
     */
    public function getIsUnderWarrantyAttribute(): bool
    {
        return $this->warranty_expiry !== null
            && $this->warranty_expiry->gte(now()->startOfDay());
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
