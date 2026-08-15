<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEscalation extends Model
{
    protected $table = 'ticket_escalations';

    protected $fillable = [
        'ticket_id',
        'escalation_rule_id',
        'escalated_at',
        'escalated_to_admin_id',
        'previous_priority',
        'new_priority',
        'notes',
        'status',
    ];

    protected $casts = [
        'escalated_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function escalationRule(): BelongsTo
    {
        return $this->belongsTo(EscalationRule::class);
    }

    public function escalatedToAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'escalated_to_admin_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
