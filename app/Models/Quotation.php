<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_number', 'lead_id', 'contact_id', 'company_id', 'opportunity_id',
        'issue_date', 'valid_until', 'amount', 'notes', 'quotation_status',
        'created_by', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'issue_date' => 'date',
        'valid_until' => 'date',
        'amount' => 'decimal:2',
    ];

    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
