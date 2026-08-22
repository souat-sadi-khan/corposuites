<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;

/**
 * Resolves a single employee's TODAY attendance state for the header widget
 * (and anywhere else that needs the same "what is this employee's status
 * right now" answer, e.g. the future My Attendance page). Kept as one
 * centralized place so this logic is never duplicated across the widget's
 * view composer, its JSON refresh endpoint, and any future consumer.
 *
 * The resolution order deliberately mirrors AttendancePortalController's own
 * check-in/check-out gating exactly (only blocks a fresh check-in when an
 * attendance row for today already has a check_in), so the widget's
 * can_check_in/can_check_out flags can never disagree with what the actual
 * check-in/check-out endpoints will accept or reject.
 */
class AttendanceStatusService
{
    public const STATES = [
        'not_checked_in', 'checked_in', 'late', 'checked_out',
        'on_leave', 'holiday', 'weekly_off', 'absent',
    ];

    public static function forEmployee(Employee $employee): array
    {
        $today = today();
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $shiftName = $employee->shift?->name ?: 'Default Shift';

        if ($attendance) {
            return self::fromAttendance($attendance, $shiftName);
        }

        $holiday = Holiday::active()->whereDate('date', $today)->first();
        if ($holiday) {
            return self::state('holiday', 'Holiday', $shiftName, canCheckIn: true, note: $holiday->name);
        }

        $weekendDays = collect(explode(',', (string) get_settings('leave_weekend_days', '5,6')))
            ->filter(fn ($d) => $d !== '')
            ->map(fn ($d) => (int) $d)
            ->all();

        if (in_array($today->dayOfWeek, $weekendDays, true)) {
            return self::state('weekly_off', 'Weekly Off', $shiftName, canCheckIn: true);
        }

        // Defensive fallback only — LeaveAttendanceService normally already
        // creates/marks an attendance row the moment a leave is approved, so
        // this branch should rarely be reached in practice.
        $onApprovedLeave = $employee->leaveRequests()
            ->where('approval_status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($onApprovedLeave) {
            return self::state('on_leave', 'On Leave', $shiftName, canCheckIn: true);
        }

        return self::state('not_checked_in', 'Not Checked In', $shiftName, canCheckIn: true);
    }

    private static function fromAttendance(Attendance $attendance, string $shiftName): array
    {
        if ($attendance->attendance_status === 'on_leave') {
            return self::state('on_leave', 'On Leave', $shiftName, canCheckIn: false);
        }

        if (!$attendance->check_in) {
            // A row exists (e.g. an admin manually marked the day 'absent')
            // but there's no punch yet — check-in is still genuinely allowed
            // by the backend, so it stays available here too.
            return self::state('absent', 'Absent', $shiftName, canCheckIn: true);
        }

        $checkInLabel = self::formatTime($attendance->check_in);
        $checkInAt = self::combine($attendance->attendance_date, $attendance->check_in);

        if ($attendance->check_out) {
            $checkOutAt = self::combine($attendance->attendance_date, $attendance->check_out);
            if ($checkOutAt->lt($checkInAt)) {
                $checkOutAt->addDay(); // overnight shift
            }

            return self::state('checked_out', 'Checked Out', $shiftName, canCheckIn: false, canCheckOut: false, checkIn: $checkInLabel, checkOut: self::formatTime($attendance->check_out), workedMinutes: (int) $checkInAt->diffInMinutes($checkOutAt));
        }

        $isLate = $attendance->attendance_status === 'late';

        return self::state(
            $isLate ? 'late' : 'checked_in',
            $isLate ? 'Late' : 'Checked In',
            $shiftName,
            canCheckIn: false,
            canCheckOut: true,
            checkIn: $checkInLabel,
            workedMinutes: (int) $checkInAt->diffInMinutes(now())
        );
    }

    private static function state(
        string $state,
        string $label,
        string $shiftName,
        bool $canCheckIn = false,
        bool $canCheckOut = false,
        ?string $checkIn = null,
        ?string $checkOut = null,
        ?int $workedMinutes = null,
        ?string $note = null
    ): array {
        return [
            'state' => $state,
            'label' => $label,
            'date_label' => now()->format('l, d F Y'),
            'shift_name' => $shiftName,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'worked_label' => $workedMinutes !== null ? self::formatDuration($workedMinutes) : null,
            'can_check_in' => $canCheckIn,
            'can_check_out' => $canCheckOut,
            'note' => $note,
        ];
    }

    private static function combine($date, string $time): Carbon
    {
        return Carbon::parse($date->toDateString() . ' ' . $time);
    }

    private static function formatTime(string $time): string
    {
        return Carbon::parse($time)->format('h:i A');
    }

    private static function formatDuration(int $minutes): string
    {
        $minutes = max(0, $minutes);
        return sprintf('%02dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }
}
