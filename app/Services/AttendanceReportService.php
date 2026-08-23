<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\EmploymentStatus;
use App\Models\Holiday;
use App\Models\Shift;
use App\Services\WeekendCalendarService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

    /**
     * Shared "Month OR explicit Date From/Date To" range parsing, used by
     * every screen that reports over a date range (My Attendance, and the
     * admin Attendance Report) so the same request params always resolve to
     * the exact same window — moved here (out of AttendancePortalController,
     * which now just delegates to this) specifically so the future PDF/
     * Excel/CSV exports can reuse it too instead of re-deriving it a third
     * time. Capped at 92 days for an explicit range so a mistyped/abusive
     * range can't force a report to compute over years of days in one
     * request.
     *
     * @return array{0: Carbon, 1: Carbon, 2: ?string} [$from, $to, $month]
     */
    public function resolveRange(Request $request): array
    {
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $from = Carbon::parse($request->input('date_from'))->startOfDay();
            $to = Carbon::parse($request->input('date_to'))->endOfDay();
            if ($to->lt($from)) {
                [$from, $to] = [$to, $from];
            }
            if ($from->diffInDays($to) > 92) {
                $to = $from->copy()->addDays(92);
            }

            return [$from, $to, null];
        }

        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01');

        return [$start->copy()->startOfMonth(), $start->copy()->endOfMonth(), $month];
    }

    /**
     * Rolls a set of per-employee buildForEmployees() results into ONE
     * organization-wide summary — for the admin Attendance Report's stat
     * cards (and later exports) — without re-deriving any per-day
     * classification logic; it only ever sums numbers buildDaysAndSummary()
     * already computed.
     *
     * @param  array<int, array{days: array, summary: array}>  $reports
     */
    public function aggregateTotals(array $reports): array
    {
        $totals = [
            'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0,
            'early_leave' => 0, 'on_leave' => 0, 'holiday' => 0, 'weekly_off' => 0,
            'worked_minutes' => 0, 'overtime_minutes' => 0, 'missing_checkouts' => 0,
            'employees_late' => 0, 'employees_missing_checkout' => 0, 'employees_overtime' => 0,
        ];

        foreach ($reports as $report) {
            $s = $report['summary'];
            foreach (['present', 'absent', 'late', 'half_day', 'early_leave', 'on_leave', 'holiday', 'weekly_off', 'worked_minutes', 'overtime_minutes', 'missing_checkouts'] as $key) {
                $totals[$key] += $s[$key] ?? 0;
            }
            if (($s['late'] ?? 0) > 0) {
                $totals['employees_late']++;
            }
            if (($s['missing_checkouts'] ?? 0) > 0) {
                $totals['employees_missing_checkout']++;
            }
            if (($s['overtime_minutes'] ?? 0) > 0) {
                $totals['employees_overtime']++;
            }
        }

        $totals['worked_label'] = $this->formatMinutes($totals['worked_minutes']);
        $totals['overtime_label'] = $this->formatMinutes($totals['overtime_minutes']);

        return $totals;
    }

    /**
     * The shared "advanced search" employee filter set — Department,
     * Designation, Shift, Employee Type, Employment Status, single Employee
     * — used identically by both the admin Attendance Report and the
     * Monthly Attendance Sheet, so the two screens can never disagree about
     * what a given filter combination actually matches.
     */
    public function filteredEmployeesQuery(Request $request)
    {
        $query = Employee::active()
            ->with(['department', 'designation', 'employeeType', 'employmentStatus', 'shift'])
            ->orderBy('first_name');

        foreach (['department_id', 'designation_id', 'shift_id', 'employee_type_id', 'employment_status_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('employee_id')) {
            $query->where('id', $request->input('employee_id'));
        }

        return $query;
    }

    /**
     * "Late Attendance" / "Missing Checkout" / "Overtime" only — narrows to
     * employees who have AT LEAST ONE such day within the already-built
     * $reports, applied in memory against summaries buildForEmployees()
     * already computed (no second query, no re-derived calculation). Prunes
     * $reports to match so a table and its own summary totals never
     * disagree about which employees are actually being shown.
     *
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     * @param  array<int, array{days: array, summary: array}>  $reports
     * @return array{0: \Illuminate\Support\Collection<int, Employee>, 1: array}
     */
    public function narrowToActivityFilters(Collection $employees, array $reports, Request $request): array
    {
        if (!$request->boolean('late_only') && !$request->boolean('missing_checkout_only') && !$request->boolean('overtime_only')) {
            return [$employees, $reports];
        }

        $employees = $employees->filter(function ($employee) use ($reports, $request) {
            $summary = $reports[$employee->id]['summary'] ?? null;
            if (!$summary) {
                return false;
            }
            if ($request->boolean('late_only') && $summary['late'] <= 0) {
                return false;
            }
            if ($request->boolean('missing_checkout_only') && $summary['missing_checkouts'] <= 0) {
                return false;
            }
            if ($request->boolean('overtime_only') && $summary['overtime_minutes'] <= 0) {
                return false;
            }
            return true;
        });

        return [$employees, array_intersect_key($reports, $employees->keyBy('id')->all())];
    }

    /**
     * The dropdown option lists both the admin Attendance Report and the
     * Monthly Attendance Sheet's advanced-search filter forms need.
     */
    public function filterOptions(): array
    {
        return [
            'departments' => Department::active()->orderBy('name')->get(),
            'designations' => Designation::active()->orderBy('name')->get(),
            'shifts' => Shift::active()->orderBy('name')->get(),
            'employeeTypes' => EmployeeType::active()->orderBy('name')->get(),
            'employmentStatuses' => EmploymentStatus::active()->orderBy('name')->get(),
            'allEmployeesForFilter' => Employee::active()->orderBy('first_name')->get(),
        ];
    }

    /**
     * A readable "Department: IT, Shift: Morning Shift" style list of
     * whichever advanced-search filters are actually applied — shown on any
     * PDF export (Attendance Report, Monthly Sheet) so it's obvious the
     * export is scoped, not the full unfiltered dataset. Built here (once,
     * shared) from the SAME $filters option lists the on-screen form itself
     * uses, so a label can never disagree with what the dropdown actually
     * shows, and so this logic exists in exactly one place rather than being
     * copy-pasted into every export controller (moved out of
     * AttendanceReportController so the Monthly Sheet's own PDF export
     * reuses it instead of re-deriving it a second time).
     */
    public function filterSummary(Request $request, array $filters): array
    {
        $lookup = function ($collection, $id) {
            return $collection->firstWhere('id', (int) $id)?->name;
        };

        $summary = [];

        if ($request->filled('department_id')) {
            $summary['Department'] = $lookup($filters['departments'], $request->department_id) ?? '—';
        }
        if ($request->filled('designation_id')) {
            $summary['Designation'] = $lookup($filters['designations'], $request->designation_id) ?? '—';
        }
        if ($request->filled('shift_id')) {
            $summary['Shift'] = $lookup($filters['shifts'], $request->shift_id) ?? '—';
        }
        if ($request->filled('employee_type_id')) {
            $summary['Employee Type'] = $lookup($filters['employeeTypes'], $request->employee_type_id) ?? '—';
        }
        if ($request->filled('employment_status_id')) {
            $summary['Employment Status'] = $lookup($filters['employmentStatuses'], $request->employment_status_id) ?? '—';
        }
        if ($request->filled('employee_id')) {
            $employee = $filters['allEmployeesForFilter']->firstWhere('id', (int) $request->employee_id);
            $summary['Employee'] = $employee ? $employee->full_name . ' (' . $employee->employee_code . ')' : '—';
        }

        $quickFilters = array_filter([
            $request->boolean('late_only') ? 'Late Attendance Only' : null,
            $request->boolean('missing_checkout_only') ? 'Missing Checkout Only' : null,
            $request->boolean('overtime_only') ? 'Overtime Only' : null,
        ]);
        if ($quickFilters) {
            $summary['Quick Filters'] = implode(', ', $quickFilters);
        }

        return $summary ?: ['Filters' => 'None (showing all active employees)'];
    }

    public function build(Employee $employee, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $records = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->with(['leaveRequest.leaveType', 'punches'])
            ->get()
            ->keyBy(fn ($row) => $row->attendance_date->toDateString());

        $holidays = $this->holidaysByDate($from, $to);

        return $this->buildDaysAndSummary($records, $holidays, $from, $to);
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
            ->with(['leaveRequest.leaveType', 'punches'])
            ->get()
            ->groupBy('employee_id');

        $holidays = $this->holidaysByDate($from, $to);

        $result = [];
        foreach ($employees as $employee) {
            $records = ($recordsByEmployee->get($employee->id) ?? collect())
                ->keyBy(fn ($row) => $row->attendance_date->toDateString());

            $result[$employee->id] = $this->buildDaysAndSummary($records, $holidays, $from, $to);
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Attendance>  $records  keyed by Y-m-d
     * @param  \Illuminate\Support\Collection<string, Holiday>  $holidays  keyed by Y-m-d
     */
    private function buildDaysAndSummary($records, $holidays, Carbon $from, Carbon $to): array
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
            $isWeekend = WeekendCalendarService::isWeekend($date);

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
                'leave_type' => $record?->leaveRequest?->leaveType?->name,
                'leave_duration_label' => $this->leaveDurationLabel($record?->leaveRequest),
            ];
        }

        $summary['worked_label'] = $this->formatMinutes($summary['worked_minutes']);
        $summary['overtime_label'] = $this->formatMinutes($summary['overtime_minutes']);
        $summary['leave_breakdown'] = $this->leaveBreakdown($days);

        return ['days' => $days, 'summary' => $summary];
    }

    /**
     * "2× Casual Leave, 1× Sick Leave (Half Day)" style summary of every
     * on_leave day in this range — computed once here (not in Blade, per
     * PART 27) so the admin Attendance Report can show WHICH leave a
     * person's "Leave" count is actually made of, not just the bare number.
     * Groups whole-day and half-day occurrences of the same leave type
     * separately, since they're not really the same thing to count together.
     *
     * @param  array<int, array>  $days
     */
    private function leaveBreakdown(array $days): ?string
    {
        $onLeaveDays = array_filter($days, fn ($day) => $day['bucket'] === 'on_leave' && $day['leave_type']);
        if (empty($onLeaveDays)) {
            return null;
        }

        $counts = [];
        foreach ($onLeaveDays as $day) {
            $label = $day['leave_type'] . ($day['leave_duration_label'] === 'Full Day' ? '' : ' (' . $day['leave_duration_label'] . ')');
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        $parts = [];
        foreach ($counts as $label => $count) {
            $parts[] = $count . '× ' . $label;
        }

        return implode(', ', $parts);
    }

    private function holidaysByDate(Carbon $from, Carbon $to)
    {
        return Holiday::active()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());
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

    /**
     * A per-DAY duration label ("Full Day" / "Half Day (First Half)") for
     * the leave that produced this specific attendance row — deliberately
     * NOT the leave request's own total_days (that's the total across its
     * whole date range, which would misleadingly repeat the same figure on
     * every single day of a multi-day leave).
     */
    private function leaveDurationLabel(?\App\Models\LeaveRequest $leaveRequest): ?string
    {
        if (!$leaveRequest) {
            return null;
        }

        if ($leaveRequest->duration_type === 'half_day') {
            return 'Half Day (' . ucwords(str_replace('_', ' ', $leaveRequest->half_day_session ?? 'half_day')) . ')';
        }

        return 'Full Day';
    }

    /**
     * The day's TOTAL worked minutes, already accumulated across every
     * closed check-in/check-out SESSION by AttendancePortalController's own
     * performCheckOut() (see attendances.worked_minutes' own migration doc
     * comment) — read directly rather than recomputed from a single
     * check_in/check_out pair, since a day can now legitimately have
     * several sessions (lunch break, a trip out and back) that a simple
     * first-in-to-last-out span would misrepresent.
     */
    private function workedMinutes(Attendance $record): int
    {
        return (int) ($record->worked_minutes ?? 0);
    }

    private function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        return sprintf('%02dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }
}
