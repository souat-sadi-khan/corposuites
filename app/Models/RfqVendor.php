<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqVendor extends Model
{
    use HasFactory;

    public const SENT_STATUSES = ['pending', 'sent', 'responded', 'declined'];

    protected $fillable = [
        'rfq_id', 'vendor_id', 'sent_status'
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
