<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierQuotation extends Model
{
    use HasFactory;

    public const STATUSES = ['received', 'selected', 'rejected', 'expired'];

    protected $fillable = [
        'quotation_number', 'rfq_id', 'vendor_id', 'quotation_date', 'valid_until',
        'subtotal', 'discount_total', 'grand_total', 'quotation_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
