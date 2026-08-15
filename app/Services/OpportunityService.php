<?php

namespace App\Services;

use App\Models\Opportunity;

class OpportunityService
{
    public function create(array $data): Opportunity
    {
        return Opportunity::create($data);
    }

    public function update(Opportunity $opportunity, array $data): Opportunity
    {
        $opportunity->update($data);
        return $opportunity;
    }

    public function delete(Opportunity $opportunity): bool
    {
        return $opportunity->delete();
    }

    public function updateStage(Opportunity $opportunity, string $stage): Opportunity
    {
        $opportunity->update(['stage' => $stage]);
        return $opportunity;
    }
}
