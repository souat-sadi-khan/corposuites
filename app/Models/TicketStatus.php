<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    protected $table = 'ticket_statuses';

    public const MAPS_TO = ['open', 'in_progress', 'on_hold', 'resolved', 'closed'];

    protected $fillable = [
        'name',
        'maps_to',
        'color',
        'sort_order',
        'description',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public function getMapsToLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->maps_to));
    }

    /**
     * Derived from maps_to rather than stored — a custom status is terminal
     * exactly when the fixed bucket it maps to is one Ticket already treats
     * as closed, so storing a second flag could only ever drift from that.
     */
    public function getIsTerminalAttribute(): bool
    {
        return in_array($this->maps_to, Ticket::CLOSED_STATUSES, true);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'ticket_status_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
