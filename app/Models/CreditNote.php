<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'issued', 'applied', 'cancelled'];

    protected $fillable = [
        'credit_note_number', 'customer_id', 'sales_invoice_id', 'credit_date', 'reason',
        'subtotal', 'discount_total', 'grand_total', 'credit_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'credit_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
