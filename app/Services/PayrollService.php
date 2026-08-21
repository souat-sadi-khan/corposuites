<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\SalaryStructureItem;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class PayrollService
{
    public function __construct(private LeaveRequestService $leaveRequests)
    {
    }

    public function create(array $data): Payroll
    {
        $structure = SalaryStructure::where('employee_id', $data['employee_id'])
            ->active()
            ->orderByDesc('effective_date')
            ->with('items.salaryComponent')
            ->first();

        $occurrenceCounts = $data['occurrence_counts'] ?? [];

        $periodBasic = $this->resolvePeriodBasic($structure, $data);
        $totals = $this->buildTotals($structure, $periodBasic, $occurrenceCounts);
        $unpaidLeaveDeduction = $this->unpaidLeaveDeduction((int) $data['employee_id'], (int) $data['month'], (int) $data['year'], $totals['total_earnings']);
        $totals['total_deductions'] += $unpaidLeaveDeduction;
        $totals['net_salary'] -= $unpaidLeaveDeduction;

        $attendanceDeduction = $this->resolveAttendanceDeductions((int) $data['employee_id'], (int) $data['month'], (int) $data['year'], $periodBasic);
        $totals['total_deductions'] += $attendanceDeduction;
        $totals['net_salary'] -= $attendanceDeduction;

        $overtime = $this->resolveOvertimeAmount((int) $data['employee_id'], (int) $data['month'], (int) $data['year'], $periodBasic);
        $totals['total_earnings'] += $overtime['amount'];
        $totals['net_salary'] += $overtime['amount'];

        $payroll = Payroll::create(array_merge(
            Arr::except($data, ['occurrence_counts']),
            [
                'salary_structure_id' => $structure?->id,
                'basic_salary' => $totals['basic_salary'],
                'total_earnings' => round($totals['total_earnings'], 2),
                'overtime_hours' => $overtime['hours'],
                'overtime_amount' => $overtime['amount'],
                'attendance_deduction' => $attendanceDeduction,
                'total_deductions' => round($totals['total_deductions'], 2),
                'net_salary' => round($totals['net_salary'], 2),
            ]
        ));

        if ($structure) {
            foreach ($structure->items as $item) {
                $resolved = $this->resolveItemAmount($item, $periodBasic, $occurrenceCounts);

                $payroll->items()->create([
                    'salary_component_id' => $item->salary_component_id,
                    'type' => $item->salaryComponent->type,
                    'amount' => $resolved['amount'],
                    'occurrence_count' => $resolved['occurrence_count'],
                ]);
            }
        }

        return $payroll;
    }

    public function update(Payroll $payroll, array $data): Payroll
    {
        $payroll->update($data);
        return $payroll;
    }

    public function delete(Payroll $payroll): bool
    {
        return $payroll->delete();
    }

    public function markAsPaid(Payroll $payroll): Payroll
    {
        $payroll->update([
            'payment_status' => 'paid',
            'payment_date' => now()->toDateString(),
        ]);

        return $payroll;
    }

    /**
     * Resolve this period's actual basic-pay figure for the structure's pay_type:
     * - monthly: the structure's fixed basic_salary, unchanged.
     * - daily: basic_salary is the per-day rate, multiplied by days actually
     *   worked (present/late = 1 day, half_day = 0.5) within the pay period.
     * - commission: basic_salary is the commission percentage, applied against
     *   the sales figure submitted for this payroll run.
     *
     * Percentage-type salary components (e.g. "House Rent = 40% of Basic") are
     * then calculated against this resolved figure, not the raw structure value,
     * so a daily/commission employee's allowances scale with what they actually
     * earned that period rather than an abstract rate.
     */
    protected function resolvePeriodBasic(?SalaryStructure $structure, array $data): float
    {
        if (!$structure) {
            return 0.0;
        }

        return match ($structure->pay_type) {
            'daily' => round(
                (float) $structure->basic_salary * $this->workedDays((int) $data['employee_id'], (int) $data['month'], (int) $data['year']),
                2
            ),
            'commission' => round(
                (float) $structure->basic_salary / 100 * (float) ($data['commission_sales_amount'] ?? 0),
                2
            ),
            default => round((float) $structure->basic_salary, 2),
        };
    }

    /**
     * Days actually worked by the employee within the resolved pay period,
     * from Attendance records. Present/late count as a full day, half_day as
     * half a day; absent/on-leave days contribute nothing (unpaid leave is
     * handled separately by unpaidLeaveDeduction() against monthly earnings).
     */
    protected function workedDays(int $employeeId, int $month, int $year): float
    {
        [$periodStart, $periodEnd] = $this->payPeriod($year, $month);

        return (float) Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get()
            ->sum(fn (Attendance $attendance) => match ($attendance->attendance_status) {
                'present', 'late' => 1,
                'half_day' => 0.5,
                default => 0,
            });
    }

    protected function buildTotals(?SalaryStructure $structure, float $periodBasic, array $occurrenceCounts = []): array
    {
        if (!$structure) {
            return ['basic_salary' => 0, 'total_earnings' => 0, 'total_deductions' => 0, 'net_salary' => 0];
        }

        $earnings = $periodBasic;
        $deductions = 0;

        foreach ($structure->items as $item) {
            $value = $this->resolveItemAmount($item, $periodBasic, $occurrenceCounts)['amount'];

            if ($item->salaryComponent->type === 'deduction') {
                $deductions += $value;
            } else {
                $earnings += $value;
            }
        }

        return [
            'basic_salary' => round($periodBasic, 2),
            'total_earnings' => round($earnings, 2),
            'total_deductions' => round($deductions, 2),
            'net_salary' => round($earnings - $deductions, 2),
        ];
    }

    /**
     * Resolve a single structure item's actual dollar value for this
     * payroll period, plus the occurrence count that produced it (null
     * unless the component is per-occurrence — kept alongside the amount
     * so an old payslip can still show "3 x $10 = $30", not just "$30").
     *
     * Fixed-type items use their own stored `amount` as-is (which may
     * carry a manual per-employee override entered on the Salary
     * Structure form).
     *
     * Percentage-type items are recalculated fresh from the component's
     * own raw percentage rate (`salaryComponent->value`) against this
     * period's resolved basic pay — not the item's stored `amount`,
     * which is only ever a dollar snapshot computed once at structure-
     * creation time against the structure's raw rate. Reusing that
     * snapshot as-is is wrong for daily/commission pay types (whose
     * period-to-period basic pay genuinely varies) and, previously, was
     * being re-divided by 100 and multiplied by periodBasic a second
     * time even for monthly employees — turning e.g. a $2,000 basic +
     * 40% ($800) allowance into $2,000 * (800/100) = $16,000 of
     * "earnings" instead of $800. Always deriving it fresh here from the
     * component's own percentage keeps it correct for every pay type,
     * and this same method backs both the totals calculation and the
     * payroll_items snapshot so the two can never disagree.
     *
     * Per-occurrence items use the item's own stored `amount` as the
     * rate (same "may carry a manual override" reasoning as fixed),
     * multiplied by the occurrence count submitted for this specific
     * payroll run — there is no meaningful count at structure-creation
     * time, since it genuinely varies every period.
     *
     * @return array{amount: float, occurrence_count: int|null}
     */
    protected function resolveItemAmount(SalaryStructureItem $item, float $periodBasic, array $occurrenceCounts = []): array
    {
        if ($item->salaryComponent->calculation_type === 'percentage') {
            return [
                'amount' => round($periodBasic * ((float) $item->salaryComponent->value / 100), 2),
                'occurrence_count' => null,
            ];
        }

        if ($item->salaryComponent->calculation_type === 'per_occurrence') {
            $count = (int) ($occurrenceCounts[$item->salary_component_id] ?? 0);
            $rate = (float) $item->amount;

            return [
                'amount' => round($rate * $count, 2),
                'occurrence_count' => $count,
            ];
        }

        return [
            'amount' => round((float) $item->amount, 2),
            'occurrence_count' => null,
        ];
    }

    /**
     * Price the overtime hours recorded on Attendance for this pay period.
     *
     * Disabled entirely (0 hours, 0 amount) unless `hrm_overtime_enabled`
     * is on. The employee's own hourly rate — used by both the multiplier
     * and tiered methods — is derived from this period's resolved basic
     * pay ($periodBasic, already correct for daily/commission pay types
     * too) divided by `hrm_overtime_standard_monthly_hours`, so overtime
     * scales with what the employee actually earns rather than a single
     * company-wide rate. The flat_rate method ignores the employee's
     * salary entirely and pays a fixed amount per OT hour instead.
     *
     * @return array{hours: float, amount: float}
     */
    protected function resolveOvertimeAmount(int $employeeId, int $month, int $year, float $periodBasic): array
    {
        if (!get_settings('hrm_overtime_enabled', false)) {
            return ['hours' => 0.0, 'amount' => 0.0];
        }

        [$periodStart, $periodEnd] = $this->payPeriod($year, $month);

        $hours = (float) Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->sum('overtime_hours');

        if ($hours <= 0) {
            return ['hours' => 0.0, 'amount' => 0.0];
        }

        $method = get_settings('hrm_overtime_calculation_method', 'multiplier');

        if ($method === 'flat_rate') {
            $rate = (float) get_settings('hrm_overtime_flat_rate', 0);

            return ['hours' => round($hours, 2), 'amount' => round($hours * $rate, 2)];
        }

        $standardMonthlyHours = (float) get_settings('hrm_overtime_standard_monthly_hours', 208);
        $hourlyRate = $standardMonthlyHours > 0 ? $periodBasic / $standardMonthlyHours : 0.0;

        if ($method === 'tiered') {
            $tier1Hours = (float) get_settings('hrm_overtime_tier1_hours', 2);
            $tier1Multiplier = (float) get_settings('hrm_overtime_tier1_multiplier', 1.5);
            $tier2Hours = (float) get_settings('hrm_overtime_tier2_hours', 2);
            $tier2Multiplier = (float) get_settings('hrm_overtime_tier2_multiplier', 2);
            $tier3Multiplier = (float) get_settings('hrm_overtime_tier3_multiplier', 2.5);

            $remaining = $hours;
            $amount = 0.0;

            $tier1Used = min($remaining, $tier1Hours);
            $amount += $tier1Used * $hourlyRate * $tier1Multiplier;
            $remaining -= $tier1Used;

            if ($remaining > 0) {
                $tier2Used = min($remaining, $tier2Hours);
                $amount += $tier2Used * $hourlyRate * $tier2Multiplier;
                $remaining -= $tier2Used;
            }

            if ($remaining > 0) {
                $amount += $remaining * $hourlyRate * $tier3Multiplier;
            }

            return ['hours' => round($hours, 2), 'amount' => round($amount, 2)];
        }

        // multiplier (default)
        $multiplier = (float) get_settings('hrm_overtime_multiplier', 1.5);

        return ['hours' => round($hours, 2), 'amount' => round($hours * $hourlyRate * $multiplier, 2)];
    }

    /**
     * Combined automatic attendance-based deduction for this pay period:
     * late arrivals and early leaves beyond their configured grace count
     * (a flat amount per occurrence, e.g. "3 free lates, then $10 each"),
     * plus unapproved absences (any day marked 'absent' that isn't already
     * covered by an approved unpaid Leave Request — those are priced by
     * unpaidLeaveDeduction() instead, so an absence is never docked twice).
     * Each rule is entirely optional via its own HRM Settings toggle.
     */
    protected function resolveAttendanceDeductions(int $employeeId, int $month, int $year, float $periodBasic): float
    {
        [$periodStart, $periodEnd] = $this->payPeriod($year, $month);

        $deduction = 0.0;

        if (get_settings('hrm_late_deduction_enabled', false)) {
            $count = $this->countAttendanceStatus($employeeId, 'late', $periodStart, $periodEnd);
            $grace = (int) get_settings('hrm_late_deduction_grace_count', 3);
            $rate = (float) get_settings('hrm_late_deduction_per_occurrence', 0);
            $deduction += max(0, $count - $grace) * $rate;
        }

        if (get_settings('hrm_early_leave_deduction_enabled', false)) {
            $count = $this->countAttendanceStatus($employeeId, 'early_leave', $periodStart, $periodEnd);
            $grace = (int) get_settings('hrm_early_leave_deduction_grace_count', 3);
            $rate = (float) get_settings('hrm_early_leave_deduction_per_occurrence', 0);
            $deduction += max(0, $count - $grace) * $rate;
        }

        if (get_settings('hrm_absent_deduction_enabled', false) && $periodBasic > 0) {
            $absentCount = $this->unapprovedAbsentCount($employeeId, $periodStart, $periodEnd);
            // Compare whole calendar days only (Carbon's diffInDays returns a
            // fractional float once microseconds are involved, e.g. when
            // $periodEnd carries endOfMonth()'s 23:59:59.999999 — normalizing
            // both sides to startOfDay() keeps this an exact day count).
            $totalDaysInPeriod = $periodStart->copy()->startOfDay()
                ->diffInDays($periodEnd->copy()->startOfDay()) + 1;
            $deduction += ($periodBasic / $totalDaysInPeriod) * $absentCount;
        }

        return round($deduction, 2);
    }

    protected function countAttendanceStatus(int $employeeId, string $status, Carbon $periodStart, Carbon $periodEnd): int
    {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->where('attendance_status', $status)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->count();
    }

    /**
     * Attendance rows marked 'absent' within the period, excluding any date
     * that already falls inside an approved unpaid Leave Request — that
     * date's pay loss is already accounted for by unpaidLeaveDeduction(),
     * so counting it here too would deduct the same day's pay twice.
     */
    protected function unapprovedAbsentCount(int $employeeId, Carbon $periodStart, Carbon $periodEnd): int
    {
        $absentDates = Attendance::query()
            ->where('employee_id', $employeeId)
            ->where('attendance_status', 'absent')
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->pluck('attendance_date');

        if ($absentDates->isEmpty()) {
            return 0;
        }

        $unpaidLeaveRanges = LeaveRequest::query()->with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('approval_status', 'approved')
            ->whereDate('start_date', '<=', $periodEnd)
            ->whereDate('end_date', '>=', $periodStart)
            ->get()
            ->filter(fn (LeaveRequest $leave) => $leave->leaveType && !$leave->leaveType->is_paid);

        if ($unpaidLeaveRanges->isEmpty()) {
            return $absentDates->count();
        }

        return $absentDates->reject(function ($date) use ($unpaidLeaveRanges) {
            $day = Carbon::parse($date);

            return $unpaidLeaveRanges->contains(
                fn (LeaveRequest $leave) => $day->betweenIncluded(
                    Carbon::parse($leave->start_date),
                    Carbon::parse($leave->end_date)
                )
            );
        })->count();
    }

    /**
     * Re-derive the unpaid-leave deduction actually applied to an already
     * generated Payroll record, for display on the payslip (it has no
     * dedicated column of its own, unlike overtime_amount/
     * attendance_deduction, since it predates both). `total_earnings` on
     * the stored record already includes overtime, which the original
     * calculation in create() did not yet have when this figure was first
     * derived — subtracting it back out reconstructs the exact same input.
     */
    public function unpaidLeaveDeductionFor(Payroll $payroll): float
    {
        $monthlyEarningsBase = (float) $payroll->total_earnings - (float) $payroll->overtime_amount;

        return $this->unpaidLeaveDeduction(
            (int) $payroll->employee_id,
            (int) $payroll->month,
            (int) $payroll->year,
            $monthlyEarningsBase
        );
    }

    protected function unpaidLeaveDeduction(int $employeeId, int $month, int $year, float $monthlyEarnings): float
    {
        if ($monthlyEarnings <= 0) {
            return 0.0;
        }

        [$periodStart, $periodEnd] = $this->payPeriod($year, $month);

        $days = LeaveRequest::query()->with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('approval_status', 'approved')
            ->whereDate('start_date', '<=', $periodEnd)
            ->whereDate('end_date', '>=', $periodStart)
            ->get()
            ->filter(fn (LeaveRequest $leave) => $leave->leaveType && !$leave->leaveType->is_paid)
            ->sum(function (LeaveRequest $leave) use ($periodStart, $periodEnd) {
                $start = Carbon::parse($leave->start_date)->max($periodStart);
                $end = Carbon::parse($leave->end_date)->min($periodEnd);
                if ($leave->duration_type === 'half_day') {
                    return $start->isSameDay($end) ? 0.5 : 0.0;
                }

                return $this->leaveRequests->workingDays($start, $end);
            });

        // Same whole-calendar-day normalization as resolveAttendanceDeductions()
        // below — diffInDays() on a raw endOfMonth() timestamp returns a
        // fractional float (e.g. 29.999999999988), which would silently
        // under/over-charge every unpaid-leave deduction by a fraction of a
        // percent.
        $totalDaysInPeriod = $periodStart->copy()->startOfDay()
            ->diffInDays($periodEnd->copy()->startOfDay()) + 1;

        return round($monthlyEarnings / $totalDaysInPeriod * $days, 2);
    }

    /**
     * Resolve the pay period window for a given payroll month/year.
     *
     * Driven by the `hrm_payroll_cutoff_day` HRM setting: 0 (the default,
     * meaning "no cutoff configured") keeps the exact original calendar-month
     * behavior (1st to end of month). A value of 1-28 shifts the period to
     * run from the day after that cutoff in the previous month through that
     * cutoff day in the payroll month — the common "26th to 25th"-style pay
     * cycle. Kept out of the `unpaidLeaveDeduction` method itself so the
     * period-resolution rule has exactly one implementation.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function payPeriod(int $year, int $month): array
    {
        $cutoffDay = (int) get_settings('hrm_payroll_cutoff_day', 0);

        if ($cutoffDay < 1 || $cutoffDay > 28) {
            $periodStart = Carbon::create($year, $month, 1)->startOfDay();

            return [$periodStart, $periodStart->copy()->endOfMonth()];
        }

        $periodEnd = Carbon::create($year, $month, $cutoffDay)->endOfDay();
        $periodStart = $periodEnd->copy()->subMonthNoOverflow()->addDay()->startOfDay();

        return [$periodStart, $periodEnd];
    }
}
