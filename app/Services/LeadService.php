<?php

namespace App\Services;

use App\Models\Lead;

class LeadService
{
    public function create(array $data): Lead
    {
        return Lead::create($data);
    }

    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);
        return $lead;
    }

    public function delete(Lead $lead): bool
    {
        return $lead->delete();
    }
}
