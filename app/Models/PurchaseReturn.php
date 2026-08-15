<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'approved', 'shipped', 'completed', 'cancelled'];

    protected $fillable = [
        'return_number', 'vendor_id', 'purchase_order_id', 'goods_receipt_id', 'return_date', 'reason',
        'return_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'return_date' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
