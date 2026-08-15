<?php

namespace App\Services;

use App\Models\SalesTarget;

class SalesTargetService
{
    public function create(array $data): SalesTarget
    {
        return SalesTarget::create($data);
    }

    public function update(SalesTarget $salesTarget, array $data): SalesTarget
    {
        $salesTarget->update($data);

        return $salesTarget;
    }

    public function delete(SalesTarget $salesTarget): bool
    {
        return $salesTarget->delete();
    }
}
