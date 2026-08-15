<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $table = 'tickets';

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const STATUSES = ['open', 'in_progress', 'on_hold', 'resolved', 'closed'];

    public const SOURCES = ['web', 'email', 'phone', 'portal', 'walk_in'];

    /**
     * A ticket that can no longer run late once it reaches one of these —
     * shared by is_overdue and the controller's overdue filter, so the two
     * can never disagree about what "still open" means.
     */
    public const CLOSED_STATUSES = ['resolved', 'closed'];

    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'ticket_category_id',
        'customer_id',
        'raised_by_employee_id',
        'requester_name',
        'requester_email',
        'requester_phone',
        'priority',
        'ticket_priority_id',
        'ticket_status',
        'ticket_status_id',
        'source',
        'due_date',
        'resolved_at',
        'closed_at',
        'sla_policy_id',
        'first_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'notes',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'first_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function getPriorityLabelAttribute(): string
    {
        return ucfirst($this->priority);
    }

    public function getTicketStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->ticket_status));
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->source === 'web' ? 'Web' : ucwords(str_replace('_', ' ', $this->source));
    }

    /**
     * The name to show for who raised the ticket — the linked customer or
     * employee if either exists, otherwise the free-text requester name.
     */
    public function getRequesterLabelAttribute(): ?string
    {
        if ($this->customer) {
            return $this->customer->name;
        }

        if ($this->raisedByEmployee) {
            return trim($this->raisedByEmployee->first_name . ' ' . $this->raisedByEmployee->last_name);
        }

        return $this->requester_name;
    }

    /**
     * Past its due date and still open. Computed rather than stored: a
     * stored flag would need a scheduled job to become true as the date
     * passes (same reasoning as Project::is_overdue/ProjectTask::is_overdue).
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_date || in_array($this->ticket_status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Past its first-response deadline, never responded to, and not
     * already closed. Computed rather than stored: a stored flag would
     * need a scheduled job to become true as the deadline passes.
     */
    public function getIsResponseBreachedAttribute(): bool
    {
        if (! $this->first_response_due_at || $this->first_responded_at || in_array($this->ticket_status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        return $this->first_response_due_at->isPast();
    }

    /**
     * Past its resolution deadline and still open. Same computed-not-stored
     * reasoning as is_response_breached above.
     */
    public function getIsResolutionBreachedAttribute(): bool
    {
        if (! $this->resolution_due_at || in_array($this->ticket_status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        return $this->resolution_due_at->isPast();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    /**
     * The SLA policy that governed this ticket's targets — purely for
     * traceability/audit; never user-selectable, always resolved
     * automatically from the ticket's own fixed priority (see
     * TicketService::withDerivedFields()).
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    /**
     * The optional, admin-configurable finer-grained status — layered on
     * top of the always-present ticket_status enum, the same relationship
     * Account Types has to Chart of Accounts' account_type enum.
     */
    public function ticketStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    /**
     * The optional, admin-configurable finer-grained priority — layered on
     * top of the always-present priority enum, the same relationship
     * ticketStatus() has to ticket_status.
     */
    public function ticketPriority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'ticket_priority_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function raisedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'raised_by_employee_id');
    }

    /**
     * Every time this ticket was actually escalated — needed by the edit
     * view's read-only escalation history block.
     */
    public function escalations(): HasMany
    {
        return $this->hasMany(TicketEscalation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Tickets that are neither resolved nor closed.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('ticket_status', self::CLOSED_STATUSES);
    }
}
