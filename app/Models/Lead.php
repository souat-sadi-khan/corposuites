<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'company_name',
        'lead_source_id', 'lead_status_id', 'assigned_to',
        'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function leadStatus(): BelongsTo
    {
        return $this->belongsTo(LeadStatus::class);
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
