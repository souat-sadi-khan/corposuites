<?php

namespace App\Services;

use App\Helpers\Images;
use App\Models\Employee;

class EmployeeService
{
    public function __construct(private LeaveAccrualService $leaveAccrualService)
    {
    }

    public function create(array $data, $photo = null): Employee
    {
        if ($photo) {
            $data['photo'] = Images::upload('employees', $photo);
        }

        $employee = Employee::create($data);

        // Phase C: auto-allocate leave balances the employee is eligible for
        // (prorated for mid-year joiners). Failure here must not block hiring.
        if ($employee->status) {
            try {
                $this->leaveAccrualService->allocateForEmployee($employee);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $employee;
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
