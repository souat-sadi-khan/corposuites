<?php

namespace App\Services;

use App\Models\Resignation;

class ResignationService
{
    public function create(array $data): Resignation
    {
        return Resignation::create($data);
    }

    public function update(Resignation $resignation, array $data): Resignation
    {
        $resignation->update($data);
        return $resignation;
    }

    public function delete(Resignation $resignation): bool
    {
        return $resignation->delete();
    }
}
