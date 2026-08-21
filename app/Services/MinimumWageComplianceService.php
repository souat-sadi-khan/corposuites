<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\MinimumWageRule;

/**
 * Single source of truth for "does this pay rate meet the configured
 * minimum wage for this employee's location" — called from both
 * SalaryStructureRequest (a form validation error on manual create/edit)
 * and SalaryTemplateService::assignToEmployees() (a per-employee skip
 * during bulk assignment, since there's no per-employee form there).
 */
class MinimumWageComplianceService
{
    /**
     * Returns null when the rate is compliant (or no rule applies at all —
     * an unconfigured location/pay type is not a violation, just nothing
     * to enforce), otherwise a plain-English message naming the shortfall.
     */
    public function violationMessage(Employee $employee, string $payType, float $rate): ?string
    {
        // Commission-based pay is inherently variable — there is no fixed
        // rate to hold against a minimum wage floor, so it's excluded here
        // exactly as it's excluded from MinimumWageRule::WAGE_TYPES.
        if (! in_array($payType, MinimumWageRule::WAGE_TYPES, true)) {
            return null;
        }

        $rule = MinimumWageRule::resolveApplicable($employee->country, $employee->state, $payType);

        if (! $rule || $rate >= (float) $rule->minimum_wage) {
            return null;
        }

        $location = $employee->state
            ? $employee->state . ', ' . $employee->country
            : $employee->country;

        return sprintf(
            'The %s pay rate (%s) is below the minimum wage of %s configured for %s.',
            $payType,
            number_format($rate, 2),
            number_format((float) $rule->minimum_wage, 2),
            $location
        );
    }
}
