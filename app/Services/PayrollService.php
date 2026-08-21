<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\LeaveRequest;
use Carbon\Carbon;

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

        $periodBasic = $this->resolvePeriodBasic($structure, $data);
        $totals = $this->buildTotals($structure, $periodBasic);
        $unpaidLeaveDeduction = $this->unpaidLeaveDeduction((int) $data['employee_id'], (int) $data['month'], (int) $data['year'], $totals['total_earnings']);
        $totals['total_deductions'] += $unpaidLeaveDeduction;
        $totals['net_salary'] -= $unpaidLeaveDeduction;

        $payroll = Payroll::create(array_merge($data, [
            'salary_structure_id' => $structure?->id,
            'basic_salary' => $totals['basic_salary'],
            'total_earnings' => $totals['total_earnings'],
            'total_deductions' => $totals['total_deductions'],
            'net_salary' => $totals['net_salary'],
        ]));

        if ($structure) {
            foreach ($structure->items as $item) {
                $payroll->items()->create([
                    'salary_component_id' => $item->salary_component_id,
                    'type' => $item->salaryComponent->type,
                    'amount' => $item->amount,
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

    protected function buildTotals(?SalaryStructure $structure, float $periodBasic): array
    {
        if (!$structure) {
            return ['basic_salary' => 0, 'total_earnings' => 0, 'total_deductions' => 0, 'net_salary' => 0];
        }

        $earnings = $periodBasic;
        $deductions = 0;

        foreach ($structure->items as $item) {
            $amount = (float) $item->amount;
            $value = $item->salaryComponent->calculation_type === 'percentage'
                ? $periodBasic * ($amount / 100)
                : $amount;

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

        $totalDaysInPeriod = $periodStart->diffInDays($periodEnd) + 1;

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
