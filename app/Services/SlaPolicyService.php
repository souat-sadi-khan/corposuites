<?php

namespace App\Services;

use App\Models\SlaPolicy;

class SlaPolicyService
{
    public function create(array $data): SlaPolicy
    {
        return SlaPolicy::create($data);
    }

    public function update(SlaPolicy $slaPolicy, array $data): SlaPolicy
    {
        $slaPolicy->update($data);

        return $slaPolicy->fresh();
    }

    public function delete(SlaPolicy $slaPolicy): bool
    {
        return $slaPolicy->delete();
    }
}
