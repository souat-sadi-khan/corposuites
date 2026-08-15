<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'lead_id', 'contact_id', 'company_id', 'amount',
        'stage', 'probability', 'expected_close_date', 'assigned_to',
        'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'amount' => 'decimal:2',
        'expected_close_date' => 'date',
    ];

    public const STAGES = ['prospecting', 'qualification', 'proposal', 'negotiation', 'won', 'lost'];

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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
