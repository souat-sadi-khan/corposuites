<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Builds a day-by-day attendance report (with summary totals) for a single
 * employee over a date range. Centralized here — per this project's own
 * PART 6 rule ("do not duplicate attendance-calculation logic separately
 * inside Blade, export classes and controller methods") — so the self-service
 * "My Attendance" page, and later the admin Attendance Report/exports, all
 * read from the exact same calculation rather than each re-deriving it.
 *
 * One query for attendance rows and one for holidays across the WHOLE range
 * (not per-day), per PART 17's performance rule — this never runs
 * employees × days individual queries.
 */
class AttendanceReportService
{
    public const CODES = [
        'present' => 'P',
        'absent' => 'A',
        'late' => 'L',
        'half_day' => 'HD',
        'early_leave' => 'EL',
        'on_leave' => 'LV',
        'holiday' => 'H',
        'weekly_off' => 'WO',
    ];

    public function build(Employee $employee, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $records = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($row) => $row->attendance_date->toDateString());

        $holidays = $this->holidaysByDate($from, $to);
        $weekendDays = $this->weekendDays();

        return $this->buildDaysAndSummary($records, $holidays, $weekendDays, $from, $to);
    }

    /**
     * Same day-by-day/summary calculation as build(), for MANY employees at
     * once — the actual monthly sheet needs one row per employee, and doing
     * that by calling build() once per employee would run 2 queries per
     * employee (still not "per day", but still one-per-employee). Instead
     * this runs exactly 2 queries TOTAL regardless of how many employees are
     * shown — one for every attendance row across all of them, one for
     * holidays — then groups in memory, per PART 17's "avoid employees ×
     * days" rule taken to its logical conclusion (avoid employees × queries
     * too).
     *
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     * @return array<int, array{days: array, summary: array}> keyed by employee_id
     */
    public function buildForEmployees($employees, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $employeeIds = $employees->pluck('id')->all();

        $recordsByEmployee = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('employee_id');

        $holidays = $this->holidaysByDate($from, $to);
        $weekendDays = $this->weekendDays();

        $result = [];
        foreach ($employees as $employee) {
            $records = ($recordsByEmployee->get($employee->id) ?? collect())
                ->keyBy(fn ($row) => $row->attendance_date->toDateString());

            $result[$employee->id] = $this->buildDaysAndSummary($records, $holidays, $weekendDays, $from, $to);
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Attendance>  $records  keyed by Y-m-d
     * @param  \Illuminate\Support\Collection<string, Holiday>  $holidays  keyed by Y-m-d
     * @param  array<int>  $weekendDays
     */
    private function buildDaysAndSummary($records, $holidays, array $weekendDays, Carbon $from, Carbon $to): array
    {
        $days = [];
        $summary = [
            'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0,
            'early_leave' => 0, 'on_leave' => 0, 'holiday' => 0, 'weekly_off' => 0,
            'worked_minutes' => 0, 'late_minutes' => 0, 'overtime_minutes' => 0,
            'missing_checkouts' => 0,
        ];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $key = $date->toDateString();
            $record = $records->get($key);
            $holiday = $holidays->get($key);
            $isWeekend = in_array($date->dayOfWeek, $weekendDays, true);

            $bucket = $this->classify($record, $holiday, $isWeekend, $date);
            $summary[$bucket] = ($summary[$bucket] ?? 0) + 1;

            $dayWorkedMinutes = 0;
            if ($record) {
                if ($record->check_in && !$record->check_out && $date->isBefore(today())) {
                    $summary['missing_checkouts']++;
                }
                $dayWorkedMinutes = $this->workedMinutes($record);
                $summary['worked_minutes'] += $dayWorkedMinutes;
                $summary['overtime_minutes'] += (int) round(((float) $record->overtime_hours) * 60);
            }

            $days[] = [
                'date' => $date->copy(),
                'record' => $record,
                'holiday' => $holiday,
                'is_weekend' => $isWeekend,
                'bucket' => $bucket,
                'code' => self::CODES[$bucket] ?? '-',
                'worked_label' => $dayWorkedMinutes > 0 ? $this->formatMinutes($dayWorkedMinutes) : '--',
            ];
        }

        $summary['worked_label'] = $this->formatMinutes($summary['worked_minutes']);
        $summary['overtime_label'] = $this->formatMinutes($summary['overtime_minutes']);

        return ['days' => $days, 'summary' => $summary];
    }

    private function holidaysByDate(Carbon $from, Carbon $to)
    {
        return Holiday::active()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());
    }

    private function weekendDays(): array
    {
        return collect(explode(',', (string) get_settings('leave_weekend_days', '5,6')))
            ->filter(fn ($d) => $d !== '')
            ->map(fn ($d) => (int) $d)
            ->all();
    }

    /**
     * Which single bucket a day falls into — attendance status wins when a
     * record exists (it's the ground truth of what actually happened, even
     * on a holiday/weekend an employee chose to work), otherwise falls back
     * to holiday, then weekend, then a plain future/absent judgment.
     */
    private function classify(?Attendance $record, ?Holiday $holiday, bool $isWeekend, Carbon $date): string
    {
        if ($record) {
            return match ($record->attendance_status) {
                'present', 'late', 'half_day', 'early_leave', 'on_leave', 'absent' => $record->attendance_status,
                default => 'present',
            };
        }

        if ($holiday) {
            return 'holiday';
        }

        if ($isWeekend) {
            return 'weekly_off';
        }

        // No record, not a holiday/weekend: only a genuinely past working
        // day with nothing logged counts as absent. Today (still in
        // progress) or a future date isn't "absent" yet — and isn't
        // "present" either, since nothing has actually happened — so both
        // get their own neutral bucket, excluded from the present/absent
        // totals entirely rather than guessed at.
        return $date->isBefore(today()) ? 'absent' : 'pending';
    }

    private function workedMinutes(Attendance $record): int
    {
        if (!$record->check_in || !$record->check_out) {
            return 0;
        }

        $in = Carbon::parse($record->attendance_date->toDateString() . ' ' . $record->check_in);
        $out = Carbon::parse($record->attendance_date->toDateString() . ' ' . $record->check_out);
        if ($out->lt($in)) {
            $out->addDay(); // overnight shift
        }

        return (int) $in->diffInMinutes($out);
    }

    private function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        return sprintf('%02dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }
}
