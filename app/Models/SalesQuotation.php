<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesQuotation extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

    protected $fillable = [
        'quotation_number', 'customer_id', 'payment_term_id', 'issue_date', 'valid_until',
        'subtotal', 'discount_total', 'grand_total', 'quotation_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'issue_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesQuotationItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
