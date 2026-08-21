<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class LeaveAttendanceService
{
    public function __construct(private LeaveRequestService $leaveRequests)
    {
    }

    public function syncApprovedLeave(LeaveRequest $leaveRequest): void
    {
        foreach ($this->leaveDates($leaveRequest) as $date) {
            $attendance = Attendance::firstOrNew([
                'employee_id' => $leaveRequest->employee_id,
                'attendance_date' => $date,
            ]);

            if ($attendance->exists && $attendance->leave_request_id && $attendance->leave_request_id !== $leaveRequest->id) {
                continue;
            }

            if ($attendance->exists && (int) $attendance->leave_request_id === (int) $leaveRequest->id) {
                continue;
            }

            if (!$attendance->exists) {
                $attendance->status = true;
            } else {
                $attendance->leave_original_status = $attendance->attendance_status;
                $attendance->leave_original_remarks = $attendance->remarks;
            }

            $attendance->fill([
                'attendance_status' => 'on_leave',
                'remarks' => 'Approved leave request #' . $leaveRequest->id,
                'leave_request_id' => $leaveRequest->id,
            ]);
            $attendance->save();
        }
    }

    public function removeLeave(LeaveRequest $leaveRequest): void
    {
        Attendance::where('leave_request_id', $leaveRequest->id)->get()->each(function (Attendance $attendance) {
            if ($attendance->leave_original_status !== null) {
                $attendance->update([
                    'attendance_status' => $attendance->leave_original_status,
                    'remarks' => $attendance->leave_original_remarks,
                    'leave_request_id' => null,
                    'leave_original_status' => null,
                    'leave_original_remarks' => null,
                ]);
                return;
            }

            $attendance->delete();
        });
    }

    private function leaveDates(LeaveRequest $leaveRequest): array
    {
        $dates = [];
        $start = Carbon::parse($leaveRequest->start_date);
        $end = Carbon::parse($leaveRequest->end_date);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($this->leaveRequests->workingDays($date, $date) > 0) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
    }
}
