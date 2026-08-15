<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Transfer;

class TransferService
{
    public function create(array $data): Transfer
    {
        $employee = Employee::with(['department', 'designation'])->find($data['employee_id']);

        if ($employee) {
            $data['from_department'] = $data['from_department'] ?: $employee->department?->name;
            $data['from_designation'] = $data['from_designation'] ?: $employee->designation?->name;

            $toDepartment = $data['to_department'] ? Department::where('name', $data['to_department'])->first() : null;
            $toDesignation = $data['to_designation'] ? Designation::where('name', $data['to_designation'])->first() : null;

            $employee->update([
                'department_id' => $toDepartment?->id ?? $employee->department_id,
                'designation_id' => $toDesignation?->id ?? $employee->designation_id,
            ]);
        }

        return Transfer::create($data);
    }

    public function update(Transfer $transfer, array $data): Transfer
    {
        $transfer->update($data);
        return $transfer;
    }

    public function delete(Transfer $transfer): bool
    {
        return $transfer->delete();
    }
}
