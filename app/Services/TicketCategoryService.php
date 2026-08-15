<?php

namespace App\Services;

use App\Models\TicketCategory;

class TicketCategoryService
{
    public function create(array $data): TicketCategory
    {
        return TicketCategory::create($data);
    }

    public function update(TicketCategory $ticketCategory, array $data): TicketCategory
    {
        $ticketCategory->update($data);

        return $ticketCategory->fresh();
    }

    public function delete(TicketCategory $ticketCategory): bool
    {
        return $ticketCategory->delete();
    }
}
