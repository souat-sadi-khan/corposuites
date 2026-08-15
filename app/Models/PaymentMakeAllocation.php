<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMakeAllocation extends Model
{
    protected $fillable = [
        'payment_make_id', 'purchase_invoice_id', 'amount_allocated'
    ];

    protected $casts = [
        'amount_allocated' => 'decimal:2',
    ];

    public function paymentMake(): BelongsTo
    {
        return $this->belongsTo(PaymentMake::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }
}
