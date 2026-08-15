<?php

namespace App\Services;

use App\Models\TicketPriority;

class TicketPriorityService
{
    public function create(array $data): TicketPriority
    {
        return TicketPriority::create($data);
    }

    public function update(TicketPriority $ticketPriority, array $data): TicketPriority
    {
        $ticketPriority->update($data);

        return $ticketPriority->fresh();
    }

    public function delete(TicketPriority $ticketPriority): bool
    {
        return $ticketPriority->delete();
    }
}
