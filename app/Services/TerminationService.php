<?php

namespace App\Services;

use App\Models\Termination;

class TerminationService
{
    public function create(array $data): Termination
    {
        return Termination::create($data);
    }

    public function update(Termination $termination, array $data): Termination
    {
        $termination->update($data);
        return $termination;
    }

    public function delete(Termination $termination): bool
    {
        return $termination->delete();
    }
}
