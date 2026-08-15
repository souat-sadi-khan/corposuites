<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    use HasFactory;

    public const PAYMENT_METHODS = ['cash', 'card', 'bank_transfer', 'other'];
    public const STATUSES = ['completed', 'voided'];

    protected $fillable = [
        'pos_number', 'customer_id', 'cashier_id', 'sold_at', 'subtotal', 'discount_total',
        'grand_total', 'payment_method', 'amount_tendered', 'change_due', 'pos_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'sold_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_due' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
