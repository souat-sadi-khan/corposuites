<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use Carbon\Carbon;

/**
 * Centralises Leave Type policy evaluation (Phase B): eligibility of an employee
 * for a leave type, and validation of a specific request against the type's rules.
 *
 * Rules are intentionally read defensively so a leave type created before the
 * policy columns existed (all defaults) behaves exactly like the old system.
 */
class LeavePolicyService
{
    /**
     * Reasons an employee is NOT eligible for a leave type. Empty array = eligible.
     */
    public function eligibilityErrors(Employee $employee, LeaveType $leaveType, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?: Carbon::today();
        $errors = [];

        // Gender restriction.
        $gender = $leaveType->applicable_gender ?? 'all';
        if ($gender !== 'all' && $employee->gender && $employee->gender !== $gender) {
            $errors[] = 'This leave type is only applicable to ' . ucfirst($gender) . ' employees.';
        }

        // Minimum service days.
        $minService = (int) ($leaveType->min_service_days ?? 0);
        if ($minService > 0 && $employee->date_of_joining) {
            $servedDays = Carbon::parse($employee->date_of_joining)->diffInDays($asOf);
            if ($servedDays < $minService) {
                $errors[] = 'Requires at least ' . $minService . ' day(s) of service; employee has '
                    . $servedDays . ' day(s).';
            }
        }

        // Employee-type restriction.
        if ($leaveType->restrictsEmployeeType()
            && !in_array((int) $employee->employee_type_id, array_map('intval', $leaveType->applicable_employee_type_ids), true)) {
            $errors[] = 'This leave type is not available for the employee\'s employment type.';
        }

        // Designation restriction.
        if ($leaveType->restrictsDesignation()
            && !in_array((int) $employee->designation_id, array_map('intval', $leaveType->applicable_designation_ids), true)) {
            $errors[] = 'This leave type is not available for the employee\'s designation.';
        }

        return $errors;
    }

    public function isEligible(Employee $employee, LeaveType $leaveType, ?Carbon $asOf = null): bool
    {
        return empty($this->eligibilityErrors($employee, $leaveType, $asOf));
    }

    /**
     * Reasons a specific request violates the leave type's request rules.
     * Empty array = valid. `$totalDays` is the already-computed working-day count.
     */
    public function requestRuleErrors(LeaveType $leaveType, $startDate, float $totalDays, bool $hasAttachment = false, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?: Carbon::today();
        $errors = [];

        // Minimum notice period (days between today and the leave start date).
        $minNotice = (int) ($leaveType->min_notice_days ?? 0);
        if ($minNotice > 0) {
            $noticeDays = $asOf->diffInDays(Carbon::parse($startDate)->startOfDay(), false);
            if ($noticeDays < $minNotice) {
                $errors[] = 'This leave type requires at least ' . $minNotice
                    . ' day(s) advance notice.';
            }
        }

        // Maximum consecutive days.
        $maxConsecutive = $leaveType->max_consecutive_days;
        if (!is_null($maxConsecutive) && $maxConsecutive > 0 && $totalDays > $maxConsecutive) {
            $errors[] = 'This leave type allows a maximum of ' . $maxConsecutive
                . ' consecutive day(s).';
        }

        // Attachment requirement.
        if ($leaveType->requires_attachment && !$hasAttachment) {
            $errors[] = 'This leave type requires a supporting document.';
        }

        return $errors;
    }
}
