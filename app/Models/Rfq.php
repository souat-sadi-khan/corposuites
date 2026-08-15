<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rfq extends Model
{
    use HasFactory;

    protected $table = 'rfqs';

    public const STATUSES = ['draft', 'sent', 'closed', 'cancelled'];

    protected $fillable = [
        'rfq_number', 'purchase_request_id', 'rfq_date', 'due_date', 'rfq_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'rfq_date' => 'date',
        'due_date' => 'date',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }

    public function rfqVendors(): HasMany
    {
        return $this->hasMany(RfqVendor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
