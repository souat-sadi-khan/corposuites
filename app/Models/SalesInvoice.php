<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled'];

    protected $fillable = [
        'invoice_number', 'customer_id', 'assigned_to', 'sales_order_id', 'payment_term_id', 'invoice_date', 'due_date',
        'subtotal', 'discount_total', 'grand_total', 'amount_paid', 'invoice_status', 'notes', 'status'
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
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
