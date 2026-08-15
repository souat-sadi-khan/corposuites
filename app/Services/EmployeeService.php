<?php

namespace App\Services;

use App\Helpers\Images;
use App\Models\Employee;

class EmployeeService
{
    public function create(array $data, $photo = null): Employee
    {
        if ($photo) {
            $data['photo'] = Images::upload('employees', $photo);
        }

        return Employee::create($data);
    }

    public function update(Employee $employee, array $data, $photo = null): Employee
    {
        if ($photo) {
            $data['photo'] = Images::update('employees', $employee->photo, $photo);
        }

        $employee->update($data);
        return $employee;
    }

    public function delete(Employee $employee): bool
    {
        if ($employee->photo) {
            Images::delete($employee->photo);
        }

        return $employee->delete();
    }
}
