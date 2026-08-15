<?php

namespace App\Services;

use App\Models\LeadSource;

class LeadSourceService
{
    public function create(array $data): LeadSource
    {
        return LeadSource::create($data);
    }

    public function update(LeadSource $leadSource, array $data): LeadSource
    {
        $leadSource->update($data);
        return $leadSource;
    }

    public function delete(LeadSource $leadSource): bool
    {
        return $leadSource->delete();
    }
}
