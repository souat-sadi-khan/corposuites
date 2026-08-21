<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveEncashment;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase C — Accrual & balance automation.
 *
 * Turns the Phase B leave-type policy into concrete `leave_balances` rows:
 *  - auto-allocation when an employee joins (prorated for mid-year joiners),
 *  - scheduled monthly accrual,
 *  - year-end carry-forward (within the type's cap) into the next year,
 *  - expiry of unused carried-forward days,
 *  - encashment of remaining balance for encashable types.
 *
 * All balance writes are idempotent per (employee, type, year) so the scheduled
 * commands can safely run more than once.
 */
class LeaveAccrualService
{
    public function __construct(private LeavePolicyService $policyService)
    {
    }

    /**
     * Ensure balance rows exist for a newly-joined (or existing) employee for the
     * given year, across every active leave type they are eligible for.
     * Annual types are prorated by remaining months when the employee joins mid-year.
     */
    public function allocateForEmployee(Employee $employee, ?int $year = null): int
    {
        $year = $year ?: (int) now()->year;
        $created = 0;

        $leaveTypes = LeaveType::active()->get();

        foreach ($leaveTypes as $leaveType) {
            if (!$this->policyService->isEligible($employee, $leaveType)) {
                continue;
            }

            if (($leaveType->accrual_method ?? 'annual') === 'none') {
                continue; // manual allocation only
            }

            $allocation = $this->initialAllocation($employee, $leaveType, $year);

            $balance = LeaveBalance::firstOrNew([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ]);

            if (!$balance->exists) {
                $balance->allocated_days = $allocation;
                $balance->used_days = 0;
                $balance->carried_days = 0;
                $balance->status = true;
                $balance->save();
                $created++;
            }
        }

        return $created;
    }

    /**
     * Initial allocation for a type in a given year. Monthly-accrual types start at
     * the accrued-so-far amount; annual types grant the full entitlement, prorated
     * by the remaining months of the joining year for mid-year joiners.
     */
    protected function initialAllocation(Employee $employee, LeaveType $leaveType, int $year): float
    {
        $entitlement = (float) $leaveType->days_allowed;

        if ($entitlement <= 0) {
            return 0.0;
        }

        if (($leaveType->accrual_method ?? 'annual') === 'monthly') {
            return $this->monthlyAccruedToDate($employee, $leaveType, $year, now());
        }

        // Annual: prorate for the joining year only.
        $joining = $employee->date_of_joining ? Carbon::parse($employee->date_of_joining) : null;

        if ($joining && $joining->year === $year && $joining->month > 1) {
            $remainingMonths = 12 - $joining->month + 1;
            return round($entitlement * $remainingMonths / 12, 2);
        }

        return $entitlement;
    }

    /**
     * Days a monthly-accrual type should have accrued from the year start (or join
     * month) through the given month. Idempotent: depends only on $asOf, not on
     * the balance's current value.
     */
    protected function monthlyAccruedToDate(Employee $employee, LeaveType $leaveType, int $year, Carbon $asOf): float
    {
        $perMonth = (float) $leaveType->days_allowed / 12;
        $joining = $employee->date_of_joining ? Carbon::parse($employee->date_of_joining) : null;

        $startMonth = ($joining && $joining->year === $year) ? $joining->month : 1;
        $endMonth = ($asOf->year === $year) ? $asOf->month : (($asOf->year > $year) ? 12 : 0);
        $months = max(0, $endMonth - $startMonth + 1);

        return round(min($perMonth * $months, (float) $leaveType->days_allowed), 2);
    }

    /**
     * Run monthly accrual for all eligible employees, catching each monthly-accrual
     * balance up to the amount it should have accrued through $asOf. Idempotent:
     * safe to run more than once for the same month, or to backfill a missed month.
     * Returns number of balances touched.
     */
    public function runMonthlyAccrual(?Carbon $asOf = null): int
    {
        $asOf = $asOf ?: Carbon::now();
        $year = (int) $asOf->year;
        $touched = 0;

        $leaveTypes = LeaveType::active()->where('accrual_method', 'monthly')->get();

        if ($leaveTypes->isEmpty()) {
            return 0;
        }

        $employees = Employee::where('status', 1)->get();

        foreach ($leaveTypes as $leaveType) {
            foreach ($employees as $employee) {
                if (!$this->policyService->isEligible($employee, $leaveType, $asOf)) {
                    continue;
                }

                // Skip employees who had not yet joined as of the accrual date.
                if ($employee->date_of_joining && Carbon::parse($employee->date_of_joining)->gt($asOf)) {
                    continue;
                }

                $target = $this->monthlyAccruedToDate($employee, $leaveType, $year, $asOf);

                DB::transaction(function () use ($employee, $leaveType, $year, $target, &$touched) {
                    $balance = LeaveBalance::lockForUpdate()->firstOrNew([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $year,
                    ]);

                    // Accrued target sits on top of any days carried in from last year.
                    $newAllocated = round($target + (float) ($balance->carried_days ?? 0), 2);

                    if ($newAllocated > (float) ($balance->allocated_days ?? 0)) {
                        $balance->allocated_days = $newAllocated;
                        $balance->status = $balance->status ?? true;
                        $balance->save();
                        $touched++;
                    }
                });
            }
        }

        return $touched;
    }

    /**
     * Year-end close: carry forward each employee's remaining balance from
     * $fromYear into $fromYear + 1 (within the type's cap), open the next year's
     * base allocation, and stamp an expiry date on the carried portion.
     * Returns number of next-year balances created/updated.
     */
    public function runYearEndCarryForward(int $fromYear): int
    {
        $toYear = $fromYear + 1;
        $processed = 0;

        $balances = LeaveBalance::with('leaveType')
            ->where('year', $fromYear)
            ->get();

        foreach ($balances as $balance) {
            $leaveType = $balance->leaveType;
            if (!$leaveType) {
                continue;
            }

            $remaining = round((float) $balance->allocated_days - (float) $balance->used_days, 2);
            $carry = 0.0;
            $expiresOn = null;

            if ($leaveType->allow_carry_forward && $remaining > 0) {
                $cap = !is_null($leaveType->max_carry_forward)
                    ? (float) $leaveType->max_carry_forward
                    : $remaining;
                $carry = min($remaining, $cap);

                $expiryMonths = (int) ($leaveType->carry_forward_expiry_months ?? 0);
                if ($expiryMonths > 0) {
                    $expiresOn = Carbon::create($toYear, 1, 1)->addMonths($expiryMonths)->toDateString();
                }
            }

            $base = ($leaveType->accrual_method ?? 'annual') === 'annual'
                ? (float) $leaveType->days_allowed
                : 0.0; // monthly types accrue during the year; none = manual

            DB::transaction(function () use ($balance, $leaveType, $toYear, $base, $carry, $expiresOn, &$processed) {
                $next = LeaveBalance::firstOrNew([
                    'employee_id' => $balance->employee_id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $toYear,
                ]);

                $next->allocated_days = round($base + $carry, 2);
                $next->carried_days = round($carry, 2);
                $next->carry_expires_on = $expiresOn;
                if (!$next->exists) {
                    $next->used_days = 0;
                }
                $next->status = true;
                $next->save();
                $processed++;
            });
        }

        return $processed;
    }

    /**
     * Expire unused carried-forward days whose expiry date has passed.
     * Carried days are treated as consumed last, so only the portion of the carry
     * that is still unused (remaining beyond the non-carried allocation) is forfeited.
     * Returns number of balances adjusted.
     */
    public function expireCarryForward(?Carbon $asOf = null): int
    {
        $asOf = $asOf ?: Carbon::today();
        $adjusted = 0;

        $balances = LeaveBalance::whereNotNull('carry_expires_on')
            ->where('carried_days', '>', 0)
            ->whereDate('carry_expires_on', '<=', $asOf->toDateString())
            ->get();

        foreach ($balances as $balance) {
            $allocated = (float) $balance->allocated_days;
            $used = (float) $balance->used_days;
            $carried = (float) $balance->carried_days;

            // Carried days are consumed last, so the still-unused carry is whatever
            // remains, capped at the carried amount.
            $unusedCarry = max(0, min($carried, round($allocated - $used, 2)));

            if ($unusedCarry > 0) {
                $balance->allocated_days = round($allocated - $unusedCarry, 2);
            }

            $balance->carried_days = 0;
            $balance->carry_expires_on = null;
            $balance->save();
            $adjusted++;
        }

        return $adjusted;
    }

    /**
     * Encash remaining balance for an encashable leave type. Records a
     * LeaveEncashment and marks the days as used so the balance reflects the payout.
     */
    public function encash(Employee $employee, LeaveType $leaveType, int $year, ?float $days = null, ?string $remarks = null): LeaveEncashment
    {
        if (!$leaveType->is_encashable) {
            throw new \InvalidArgumentException('This leave type is not encashable.');
        }

        return DB::transaction(function () use ($employee, $leaveType, $year, $days, $remarks) {
            $balance = LeaveBalance::lockForUpdate()->firstOrNew([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ]);

            $remaining = round((float) ($balance->allocated_days ?? 0) - (float) ($balance->used_days ?? 0), 2);
            $encashDays = is_null($days) ? $remaining : min($days, $remaining);

            if ($encashDays <= 0) {
                throw new \InvalidArgumentException('No encashable balance available.');
            }

            $balance->used_days = (float) ($balance->used_days ?? 0) + $encashDays;
            $balance->status = $balance->status ?? true;
            $balance->save();

            return LeaveEncashment::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'days' => $encashDays,
                'status' => 'pending',
                'remarks' => $remarks,
            ]);
        });
    }
}
