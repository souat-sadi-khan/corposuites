<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceiveAllocation extends Model
{
    protected $fillable = [
        'payment_receive_id', 'sales_invoice_id', 'amount_allocated'
    ];

    protected $casts = [
        'amount_allocated' => 'decimal:2',
    ];

    public function paymentReceive(): BelongsTo
    {
        return $this->belongsTo(PaymentReceive::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}
