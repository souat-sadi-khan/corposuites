<?php

namespace App\Services;

use App\Models\TicketAssignment;

class TicketAssignmentService
{
    // Plain create()/update()/delete() — no cross-entity side effect on the
    // ticket's own ticket_status. Unlike Asset Assignment (which keeps
    // assets.asset_status truthful), a ticket's work-progress state is a
    // separate concern owned by the Tickets screen itself, and "Ticket
    // Status" is its own, not-yet-built roadmap item — deliberately not
    // driven automatically from assignment activity here.

    public function create(array $data): TicketAssignment
    {
        return TicketAssignment::create($data);
    }

    public function update(TicketAssignment $ticketAssignment, array $data): TicketAssignment
    {
        $ticketAssignment->update($data);

        return $ticketAssignment->fresh();
    }

    public function delete(TicketAssignment $ticketAssignment): bool
    {
        return $ticketAssignment->delete();
    }
}
