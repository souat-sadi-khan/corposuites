<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    protected $table = 'sla_policies';

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    protected $fillable = [
        'name',
        'priority',
        'response_time_hours',
        'resolution_time_hours',
        'description',
        'status',
    ];

    protected $casts = [
        'response_time_hours' => 'decimal:2',
        'resolution_time_hours' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function getPriorityLabelAttribute(): string
    {
        return ucfirst($this->priority);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'sla_policy_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
