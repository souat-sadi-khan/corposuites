<?php

namespace App\Services;

use App\Models\FollowUp;

class FollowUpService
{
    public function create(array $data): FollowUp
    {
        return FollowUp::create($data);
    }

    public function update(FollowUp $followUp, array $data): FollowUp
    {
        $followUp->update($data);
        return $followUp;
    }

    public function delete(FollowUp $followUp): bool
    {
        return $followUp->delete();
    }

    public function markCompleted(FollowUp $followUp, bool $isCompleted): FollowUp
    {
        $followUp->update(['is_completed' => $isCompleted]);
        return $followUp;
    }
}
