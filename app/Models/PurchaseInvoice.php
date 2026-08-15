<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;

    public const MATCH_STATUSES = ['unmatched', 'matched', 'discrepancy'];
    public const STATUSES = ['pending', 'approved', 'paid', 'disputed', 'cancelled'];

    protected $fillable = [
        'invoice_number', 'vendor_id', 'purchase_order_id', 'goods_receipt_id', 'invoice_date', 'due_date',
        'subtotal', 'discount_total', 'grand_total', 'amount_paid', 'match_status', 'invoice_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    protected $appends = ['balance_due'];

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
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function getBalanceDueAttribute(): float
    {
        return round((float) $this->grand_total - (float) $this->amount_paid, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
