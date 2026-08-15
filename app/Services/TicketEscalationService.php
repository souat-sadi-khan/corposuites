<?php

namespace App\Services;

use App\Models\EscalationRule;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketEscalation;

class TicketEscalationService
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Applies the applicable EscalationRule to a breached ticket and logs
     * what happened. Deliberately a manual, admin-triggered action, not an
     * automatic background job — nothing in this project runs a scheduled
     * "check every open ticket" sweep, and inventing one here would be a
     * different, much larger feature (a real job scheduler/queue concern)
     * than this checklist item calls for.
     */
    public function escalate(Ticket $ticket, ?string $notes = null): TicketEscalation
    {
        if (in_array($ticket->ticket_status, Ticket::CLOSED_STATUSES, true)) {
            throw new \RuntimeException('This ticket is already resolved or closed — there is nothing to escalate.');
        }

        // Resolution breach is the more severe of the two, so it takes
        // precedence when a ticket has breached both targets at once.
        $trigger = $ticket->is_resolution_breached
            ? 'resolution_breach'
            : ($ticket->is_response_breached ? 'response_breach' : null);

        if (! $trigger) {
            throw new \RuntimeException('This ticket has not breached its SLA — there is nothing to escalate.');
        }

        $rule = EscalationRule::where('priority', $ticket->priority)
            ->where('trigger', $trigger)
            ->where('status', true)
            ->first();

        if (! $rule) {
            throw new \RuntimeException(
                'No active escalation rule is configured for ' . ucfirst($ticket->priority)
                . ' priority ' . str_replace('_', ' ', $trigger) . '.'
            );
        }

        $previousPriority = $ticket->priority;

        if ($rule->escalate_priority_to && $rule->escalate_priority_to !== $ticket->priority) {
            $ticket = $this->ticketService->changePriority($ticket, $rule->escalate_priority_to);
        }

        if ($rule->escalate_to_admin_id) {
            $this->reassignTo($ticket, $rule->escalate_to_admin_id);
        }

        return TicketEscalation::create([
            'ticket_id' => $ticket->id,
            'escalation_rule_id' => $rule->id,
            'escalated_at' => now(),
            'escalated_to_admin_id' => $rule->escalate_to_admin_id,
            'previous_priority' => $previousPriority,
            'new_priority' => $ticket->priority,
            'notes' => $notes,
            'status' => true,
        ]);
    }

    /**
     * Closes out any existing active assignment before opening a new one —
     * TicketAssignmentService::create() is a plain pass-through with no
     * validation of its own (the one-active-assignment-per-ticket rule
     * lives entirely in TicketAssignmentRequest), so calling it directly
     * here would silently allow two simultaneous "assigned" rows for the
     * same ticket unless this method enforces the same close-then-create
     * sequence a human reassigning a ticket through that screen would
     * otherwise have to do by hand.
     */
    protected function reassignTo(Ticket $ticket, int $adminId): void
    {
        TicketAssignment::where('ticket_id', $ticket->id)
            ->where('assignment_status', 'assigned')
            ->update(['assignment_status' => 'reassigned']);

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'assigned_to' => $adminId,
            'assigned_date' => now()->toDateString(),
            'assignment_status' => 'assigned',
            'status' => true,
        ]);
    }
}
