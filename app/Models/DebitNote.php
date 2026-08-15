<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebitNote extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'issued', 'applied', 'cancelled'];

    protected $fillable = [
        'debit_note_number', 'vendor_id', 'purchase_invoice_id', 'debit_date', 'reason',
        'subtotal', 'discount_total', 'grand_total', 'debit_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'debit_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DebitNoteItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
