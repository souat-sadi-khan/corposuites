<?php

namespace App\Services;

use App\Models\LeadStatus;

class LeadStatusService
{
    public function create(array $data): LeadStatus
    {
        return LeadStatus::create($data);
    }

    public function update(LeadStatus $leadStatus, array $data): LeadStatus
    {
        $leadStatus->update($data);
        return $leadStatus;
    }

    public function delete(LeadStatus $leadStatus): bool
    {
        return $leadStatus->delete();
    }
}
