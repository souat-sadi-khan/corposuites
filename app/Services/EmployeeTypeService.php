<?php

namespace App\Services;

use App\Models\EmployeeType;

class EmployeeTypeService
{
    public function create(array $data): EmployeeType
    {
        return EmployeeType::create($data);
    }

    public function update(EmployeeType $employeeType, array $data): EmployeeType
    {
        $employeeType->update($data);
        return $employeeType;
    }

    public function delete(EmployeeType $employeeType): bool
    {
        return $employeeType->delete();
    }
}
