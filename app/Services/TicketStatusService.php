<?php

namespace App\Services;

use App\Models\TicketStatus;

class TicketStatusService
{
    public function create(array $data): TicketStatus
    {
        return TicketStatus::create($data);
    }

    public function update(TicketStatus $ticketStatus, array $data): TicketStatus
    {
        $ticketStatus->update($data);

        return $ticketStatus->fresh();
    }

    public function delete(TicketStatus $ticketStatus): bool
    {
        return $ticketStatus->delete();
    }
}
