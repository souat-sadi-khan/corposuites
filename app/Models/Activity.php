<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'subject', 'description', 'due_date',
        'lead_id', 'contact_id', 'company_id', 'opportunity_id',
        'assigned_to', 'activity_status', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'due_date' => 'datetime',
    ];

    public const TYPES = ['call', 'meeting', 'email'];
    public const ACTIVITY_STATUSES = ['pending', 'completed', 'cancelled'];

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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
