<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code', 'customer_group_id', 'payment_term_id', 'name', 'email', 'phone', 'company_name',
        'billing_address', 'shipping_address', 'tax_number', 'credit_limit_enabled',
        'credit_limit', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'credit_limit_enabled' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
