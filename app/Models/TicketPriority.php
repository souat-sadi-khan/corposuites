<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPriority extends Model
{
    protected $table = 'ticket_priorities';

    public const MAPS_TO = ['low', 'medium', 'high', 'urgent'];

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
        return ucfirst($this->maps_to);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'ticket_priority_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
