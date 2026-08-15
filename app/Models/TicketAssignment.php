<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAssignment extends Model
{
    protected $table = 'ticket_assignments';

    public const STATUSES = ['assigned', 'reassigned', 'cancelled'];

    protected $fillable = [
        'ticket_id',
        'assigned_to',
        'assigned_date',
        'assignment_status',
        'notes',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'status' => 'boolean',
    ];

    /**
     * Still the current handler of the ticket — the state that blocks the
     * ticket from being actively assigned to anyone else.
     */
    public function getIsActiveAssignmentAttribute(): bool
    {
        return $this->assignment_status === 'assigned';
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
