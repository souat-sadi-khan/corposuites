<?php

namespace App\Services;

use App\Models\LeaveBalance;

class LeaveBalanceService
{
    public function create(array $data): LeaveBalance
    {
        return LeaveBalance::create($data);
    }

    public function update(LeaveBalance $leaveBalance, array $data): LeaveBalance
    {
        $leaveBalance->update($data);
        return $leaveBalance;
    }

    public function delete(LeaveBalance $leaveBalance): bool
    {
        return $leaveBalance->delete();
    }
}
