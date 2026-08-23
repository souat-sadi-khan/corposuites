<?php

namespace App\Services;

use App\Models\LeaveBalance;
use Illuminate\Support\Collection;

class LeaveBalanceService
{
    /**
     * The write path behind the "one employee, one year, one record — with
     * multiple leave types under it" master-detail Manage screen. The
     * underlying `leave_balances` table is UNCHANGED (still one row per
     * employee+leave_type+year, per its own unique index) — this only
     * changes how the admin UI edits several of those rows at once, as one
     * employee+year group.
     *
     * Deliberately an upsert-and-prune, not the "delete every existing line
     * then recreate them all" convention this project's other master-detail
     * screens (Project Budgets, Journal Entries, ...) use for THEIR own
     * items tables — those items carry no meaning outside their own parent
     * document, but a LeaveBalance row's id is exactly the tuple
     * LeaveAccrualService/LeaveRequestService::adjustBalanceFor() already
     * key every accrual/deduction off via firstOrNew()/firstOrCreate(). A
     * blind delete+recreate would still be numerically correct (nothing
     * else in this codebase stores a persisted LeaveBalance id — every
     * other consumer looks the row up by the employee_id+leave_type_id+year
     * tuple, confirmed by grep across the app), but recycling the row's id
     * on every save is needless churn this upsert avoids for free.
     *
     * @param  array<int, array{leave_type_id: int, allocated_days: float, used_days: float, carried_days?: float, carry_expires_on?: ?string, status?: bool}>  $items
     * @return Collection<int, LeaveBalance>
     */
    public function saveGroup(int $employeeId, int $year, array $items): Collection
    {
        $keptLeaveTypeIds = [];
        $saved = collect();

        foreach ($items as $item) {
            $leaveTypeId = (int) $item['leave_type_id'];
            $keptLeaveTypeIds[] = $leaveTypeId;

            $saved->push(LeaveBalance::updateOrCreate(
                ['employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId, 'year' => $year],
                [
                    'allocated_days' => $item['allocated_days'] ?? 0,
                    'used_days' => $item['used_days'] ?? 0,
                    'carried_days' => $item['carried_days'] ?? 0,
                    'carry_expires_on' => $item['carry_expires_on'] ?? null,
                    'status' => $item['status'] ?? true,
                ]
            ));
        }

        // Prune only leave types that existed before this save but were
        // removed from the resubmitted set — a deliberate deletion the
        // admin made in the Manage form, not a side effect of anything else.
        LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->when(!empty($keptLeaveTypeIds), fn ($q) => $q->whereNotIn('leave_type_id', $keptLeaveTypeIds))
            ->when(empty($keptLeaveTypeIds), fn ($q) => $q) // an empty submission removes the whole group
            ->delete();

        return $saved;
    }

    /**
     * Deletes an entire employee+year group (every leave type under it) —
     * the "Delete" action on the grouped index row.
     */
    public function deleteGroup(int $employeeId, int $year): int
    {
        return LeaveBalance::where('employee_id', $employeeId)->where('year', $year)->delete();
    }
}
