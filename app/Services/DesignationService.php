<?php

namespace App\Services;

use App\Models\Designation;

class DesignationService
{
    public function create(array $data): Designation
    {
        return Designation::create($data);
    }

    public function update(Designation $designation, array $data): Designation
    {
        $designation->update($data);
        return $designation;
    }

    public function delete(Designation $designation): bool
    {
        return $designation->delete();
    }
}
