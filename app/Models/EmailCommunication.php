<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCommunication extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction', 'from_email', 'to_email', 'subject', 'body', 'sent_at',
        'lead_id', 'contact_id', 'company_id', 'created_by', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public const DIRECTIONS = ['inbound', 'outbound'];

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
