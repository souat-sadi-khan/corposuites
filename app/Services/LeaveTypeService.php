<?php

namespace App\Services;

use App\Models\LeaveType;

class LeaveTypeService
{
    public function create(array $data): LeaveType
    {
        return LeaveType::create($data);
    }

    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update($data);
        return $leaveType;
    }

    public function delete(LeaveType $leaveType): bool
    {
        return $leaveType->delete();
    }
}
