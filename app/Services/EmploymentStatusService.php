<?php

namespace App\Services;

use App\Models\EmploymentStatus;

class EmploymentStatusService
{
    public function create(array $data): EmploymentStatus
    {
        return EmploymentStatus::create($data);
    }

    public function update(EmploymentStatus $employmentStatus, array $data): EmploymentStatus
    {
        $employmentStatus->update($data);
        return $employmentStatus;
    }

    public function delete(EmploymentStatus $employmentStatus): bool
    {
        return $employmentStatus->delete();
    }
}
