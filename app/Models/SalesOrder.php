<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];

    protected $fillable = [
        'order_number', 'customer_id', 'assigned_to', 'sales_quotation_id', 'payment_term_id', 'order_date',
        'expected_delivery_date', 'subtotal', 'discount_total', 'grand_total', 'order_status',
        'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function salesQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
