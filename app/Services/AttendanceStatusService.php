<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendancePunch;
use App\Models\Employee;
use App\Models\Holiday;
use App\Services\WeekendCalendarService;
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

        if (WeekendCalendarService::isWeekend($today)) {
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

    /**
     * Multiple check-in/check-out cycles are allowed per day now (see
     * AttendancePortalController::performCheckIn/performCheckOut) — the
     * widget's own can_check_in flag mirrors that exactly: it's true
     * whenever there's no CURRENTLY OPEN session (the latest punch today,
     * or an overnight one still open from yesterday, is a check_out or
     * doesn't exist yet), regardless of whether the employee already
     * checked out earlier today. "Checked Out" is therefore no longer a
     * dead end — the Check In button reappears so a second session can
     * start (back from lunch, back from a client visit, etc.).
     */
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

        $sessions = self::sessionsFor($attendance);
        $latestPunch = AttendancePunch::latestFor($attendance->employee_id, today());
        $isOpen = $latestPunch && $latestPunch->punch_type === 'check_in';

        $checkInLabel = self::formatTime($attendance->check_in);
        $todaysWorkedMinutes = (int) $attendance->worked_minutes;

        if ($isOpen) {
            $isLate = $attendance->attendance_status === 'late';
            // Running total = every already-closed session today PLUS the
            // currently open one's elapsed time so far, so "worked today"
            // reads correctly mid-session, not just after the first pair.
            $runningMinutes = $todaysWorkedMinutes + (int) $latestPunch->punched_at->diffInMinutes(now());

            return self::state(
                $isLate ? 'late' : 'checked_in',
                $isLate ? 'Late' : 'Checked In',
                $shiftName,
                canCheckIn: false,
                canCheckOut: true,
                checkIn: $checkInLabel,
                workedMinutes: $runningMinutes,
                sessions: $sessions
            );
        }

        // Not currently open — either never checked in, or checked out and
        // free to check back in. Either way canCheckIn stays true.
        return self::state(
            'checked_out',
            'Checked Out',
            $shiftName,
            canCheckIn: true,
            canCheckOut: false,
            checkIn: $checkInLabel,
            checkOut: $attendance->check_out ? self::formatTime($attendance->check_out) : null,
            workedMinutes: $todaysWorkedMinutes,
            sessions: $sessions
        );
    }

    /**
     * Every session recorded today, paired up (check_in followed by its
     * matching check_out) for display — a trailing unmatched check_in is
     * still "open" and shown as such rather than silently dropped. Kept
     * here (not in Blade, per this project's own "no calculation in views"
     * rule) so the header widget and any future consumer read the exact
     * same session breakdown.
     */
    private static function sessionsFor(Attendance $attendance): array
    {
        $punches = AttendancePunch::where('employee_id', $attendance->employee_id)
            ->where('attendance_date', $attendance->attendance_date->toDateString())
            ->orderBy('punched_at')
            ->get();

        $sessions = [];
        $open = null;

        foreach ($punches as $punch) {
            if ($punch->punch_type === 'check_in') {
                $open = $punch;
                continue;
            }

            // check_out
            $sessions[] = [
                'check_in' => $open ? self::formatTime($open->punched_at->format('H:i:s')) : null,
                'check_out' => self::formatTime($punch->punched_at->format('H:i:s')),
                'source' => $punch->source_label,
                'notes' => $punch->notes,
                'is_open' => false,
            ];
            $open = null;
        }

        if ($open) {
            $sessions[] = [
                'check_in' => self::formatTime($open->punched_at->format('H:i:s')),
                'check_out' => null,
                'source' => $open->source_label,
                'notes' => $open->notes,
                'is_open' => true,
            ];
        }

        return $sessions;
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
        ?string $note = null,
        array $sessions = []
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
            'sessions' => $sessions,
        ];
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
