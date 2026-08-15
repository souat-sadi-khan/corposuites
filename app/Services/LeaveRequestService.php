<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class LeaveRequestService
{
    public function create(array $data): LeaveRequest
    {
        $data['total_days'] = $this->calculateDays($data['start_date'], $data['end_date']);

        return LeaveRequest::create($data);
    }

    public function update(LeaveRequest $leaveRequest, array $data): LeaveRequest
    {
        $data['total_days'] = $this->calculateDays($data['start_date'], $data['end_date']);

        $leaveRequest->update($data);
        return $leaveRequest;
    }

    public function delete(LeaveRequest $leaveRequest): bool
    {
        if ($leaveRequest->approval_status === 'approved') {
            $this->adjustBalance($leaveRequest, -$leaveRequest->total_days);
        }

        return $leaveRequest->delete();
    }

    public function approve(LeaveRequest $leaveRequest): LeaveRequest
    {
        $leaveRequest->update(['approval_status' => 'approved']);
        $this->adjustBalance($leaveRequest, $leaveRequest->total_days);

        return $leaveRequest;
    }

    public function reject(LeaveRequest $leaveRequest): LeaveRequest
    {
        $leaveRequest->update(['approval_status' => 'rejected']);
        return $leaveRequest;
    }

    protected function calculateDays($startDate, $endDate): float
    {
        return Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
    }

    protected function adjustBalance(LeaveRequest $leaveRequest, float $days): void
    {
        $balance = LeaveBalance::firstOrCreate(
            [
                'employee_id' => $leaveRequest->employee_id,
                'leave_type_id' => $leaveRequest->leave_type_id,
                'year' => Carbon::parse($leaveRequest->start_date)->year,
            ],
            ['allocated_days' => 0, 'used_days' => 0]
        );

        $balance->update(['used_days' => max(0, $balance->used_days + $days)]);
    }
}
