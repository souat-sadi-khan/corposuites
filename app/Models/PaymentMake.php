<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMake extends Model
{
    use HasFactory;

    public const METHODS = ['cash', 'bank_transfer', 'cheque', 'card', 'other'];

    protected $fillable = [
        'payment_number', 'vendor_id', 'finance_bank_account_id', 'payment_date',
        'payment_method', 'reference', 'amount', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function financeBankAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceBankAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentMakeAllocation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
