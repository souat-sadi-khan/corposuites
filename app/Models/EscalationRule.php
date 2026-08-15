<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EscalationRule extends Model
{
    protected $table = 'escalation_rules';

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const TRIGGERS = ['response_breach', 'resolution_breach'];

    protected $fillable = [
        'name',
        'priority',
        'trigger',
        'escalate_to_admin_id',
        'escalate_priority_to',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getPriorityLabelAttribute(): string
    {
        return ucfirst($this->priority);
    }

    public function getTriggerLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->trigger));
    }

    public function escalateToAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'escalate_to_admin_id');
    }

    public function ticketEscalations(): HasMany
    {
        return $this->hasMany(TicketEscalation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
