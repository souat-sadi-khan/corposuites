<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'partially_received', 'completed', 'cancelled'];

    protected $fillable = [
        'po_number', 'vendor_id', 'purchase_request_id', 'rfq_id', 'supplier_quotation_id',
        'order_date', 'expected_delivery_date', 'subtotal', 'discount_total', 'grand_total',
        'order_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplierQuotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
