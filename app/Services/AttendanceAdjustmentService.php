<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceAdjustment;

class AttendanceAdjustmentService
{
    public function create(array $data): AttendanceAdjustment
    {
        return AttendanceAdjustment::create($data);
    }

    public function update(AttendanceAdjustment $attendanceAdjustment, array $data): AttendanceAdjustment
    {
        $attendanceAdjustment->update($data);
        return $attendanceAdjustment;
    }

    public function delete(AttendanceAdjustment $attendanceAdjustment): bool
    {
        return $attendanceAdjustment->delete();
    }

    public function approve(AttendanceAdjustment $attendanceAdjustment): AttendanceAdjustment
    {
        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $attendanceAdjustment->employee_id,
                'attendance_date' => $attendanceAdjustment->adjustment_date,
            ],
            [
                'check_in' => $attendanceAdjustment->requested_check_in ?? null,
                'check_out' => $attendanceAdjustment->requested_check_out ?? null,
                'attendance_status' => 'present',
            ]
        );

        $attendanceAdjustment->update([
            'approval_status' => 'approved',
            'attendance_id' => $attendance->id,
        ]);

        return $attendanceAdjustment;
    }

    public function reject(AttendanceAdjustment $attendanceAdjustment): AttendanceAdjustment
    {
        $attendanceAdjustment->update(['approval_status' => 'rejected']);
        return $attendanceAdjustment;
    }
}
