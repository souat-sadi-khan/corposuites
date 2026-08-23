<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Single source of truth for "is this calendar date a non-working
 * (weekend/off) day" — used by both the Attendance module (attendance
 * sheet/report/header-widget classification) and the Leave module
 * (leave-day counting in LeaveRequestService), so the two can never
 * disagree about which days don't count as duty days. Centralizing this
 * (rather than each module re-deriving its own weekend check) is the same
 * "one calculation, many consumers" rule this project's Attendance work has
 * followed throughout (see AttendanceReportService's own doc comment).
 *
 * Supports two configurable modes (HRM Settings -> Attendance & Time):
 *
 *  - day_of_week (default, unchanged from before): a fixed set of weekdays
 *    (e.g. Friday + Saturday) is off every single week — a normal 7-day
 *    week with 1-2 fixed rest days.
 *
 *  - date_parity: every EVEN or every ODD calendar date of the month is
 *    off instead (e.g. a real office schedule: the 2nd/4th/6th/8th... are
 *    rest days, the 1st/3rd/5th/7th... are working days) — a genuinely
 *    different kind of schedule that has nothing to do with the day of the
 *    week at all, and can't be expressed as a set of weekday numbers.
 */
class WeekendCalendarService
{
    public const MODES = ['day_of_week', 'date_parity'];
    public const PARITIES = ['even', 'odd'];

    /**
     * Whether the given calendar date is a configured non-working day,
     * under whichever mode is currently active.
     */
    public static function isWeekend(Carbon $date): bool
    {
        if (self::mode() === 'date_parity') {
            $isEvenDate = $date->day % 2 === 0;
            $offParity = self::parity();

            return $offParity === 'even' ? $isEvenDate : !$isEvenDate;
        }

        return in_array($date->dayOfWeek, self::weekdayNumbers(), true);
    }

    public static function mode(): string
    {
        $mode = (string) get_settings('hrm_weekend_mode', 'day_of_week');

        return in_array($mode, self::MODES, true) ? $mode : 'day_of_week';
    }

    public static function parity(): string
    {
        $parity = (string) get_settings('hrm_weekend_parity', 'even');

        return in_array($parity, self::PARITIES, true) ? $parity : 'even';
    }

    /**
     * Only meaningful in day_of_week mode — the configured Carbon day
     * numbers (0=Sunday..6=Saturday) that are off every week. Kept public
     * (not folded entirely into isWeekend()) because the HRM Settings page
     * itself still needs the raw configured set to pre-select the "Weekly
     * Off Days" multi-select, independent of which mode is currently on.
     */
    public static function weekdayNumbers(): array
    {
        return collect(explode(',', (string) get_settings('leave_weekend_days', '5,6')))
            ->filter(fn ($d) => $d !== '')
            ->map(fn ($d) => (int) trim($d))
            ->filter(fn ($d) => $d >= 0 && $d <= 6)
            ->unique()
            ->values()
            ->all();
    }
}
