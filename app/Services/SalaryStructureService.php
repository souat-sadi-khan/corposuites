<?php

namespace App\Services;

use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use Illuminate\Support\Collection;

class SalaryStructureService
{
    public function create(array $data): SalaryStructure
    {
        $components = $data['components'] ?? [];

        unset($data['components']);

        $data['gross_salary'] = $this->calculateGross(
            $data['basic_salary'],
            $components
        );

        $salaryStructure = SalaryStructure::create($data);

        $this->syncItems(
            $salaryStructure,
            $components
        );

        return $salaryStructure;
    }

    public function update(
        SalaryStructure $salaryStructure,
        array $data
    ): SalaryStructure {
        $components = $data['components'] ?? [];

        unset($data['components']);

        $data['gross_salary'] = $this->calculateGross(
            $data['basic_salary'],
            $components
        );

        $salaryStructure->update($data);

        $this->syncItems(
            $salaryStructure,
            $components
        );

        return $salaryStructure;
    }

    public function delete(
        SalaryStructure $salaryStructure
    ): bool {
        return $salaryStructure->delete();
    }

    /**
     * Add or update a single component on each selected employee's active
     * Salary Structure (the same "active, latest effective_date" structure
     * PayrollService itself resolves) without touching any of that
     * employee's other components — i.e. "assign this one component to a
     * group of employees at once" rather than replacing their whole
     * structure the way a Salary Template assignment does.
     *
     * When $amountOverride is null, the amount is calculated per employee
     * from the component's own fixed/percentage rule against that
     * employee's own basic salary (so a percentage component correctly
     * differs per employee); when given, every employee receives the same
     * flat override amount.
     *
     * @param  array<int, int|string>  $employeeIds
     * @return array{updated: Collection<int, SalaryStructure>, skipped: Collection<int, array{employee_id: int, reason: string}>}
     */
    public function assignComponentToEmployees(
        SalaryComponent $component,
        array $employeeIds,
        ?float $amountOverride = null
    ): array {
        $updated = collect();
        $skipped = collect();

        foreach ($employeeIds as $employeeId) {

            $structure = SalaryStructure::where('employee_id', $employeeId)
                ->active()
                ->orderByDesc('effective_date')
                ->first();

            if (!$structure) {
                $skipped->push([
                    'employee_id' => (int) $employeeId,
                    'reason' => 'No active salary structure',
                ]);

                continue;
            }

            $amount = $amountOverride ?? $this->calculateComponentAmount(
                (float) $structure->basic_salary,
                $component
            );

            $structure->items()->updateOrCreate(
                ['salary_component_id' => $component->id],
                ['amount' => $amount]
            );

            $structure->gross_salary = $this->recalculateGrossFromItems($structure);
            $structure->save();

            $updated->push($structure);
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Recompute gross salary by summing the structure's own stored item
     * amounts (respecting each item's own component type) rather than
     * re-deriving every item from its component definition — a structure's
     * item amounts may already carry a manual per-employee override that
     * must not be silently discarded by a bulk assignment touching a
     * different component.
     */
    protected function recalculateGrossFromItems(SalaryStructure $structure): float
    {
        $gross = (float) $structure->basic_salary;

        foreach ($structure->items()->with('salaryComponent')->get() as $item) {

            if (!$item->salaryComponent) {
                continue;
            }

            if ($item->salaryComponent->type === 'deduction') {
                $gross -= (float) $item->amount;
            } else {
                $gross += (float) $item->amount;
            }
        }

        return round($gross, 2);
    }

    /**
     * Sync salary structure components.
     *
     * The amount stored in salary_structure_items
     * is always the final calculated amount.
     */
    protected function syncItems(
        SalaryStructure $salaryStructure,
        array $components
    ): void {
        $salaryStructure->items()->delete();

        foreach ($components as $component) {

            if (
                empty($component['salary_component_id'])
            ) {
                continue;
            }

            $definition = SalaryComponent::find(
                $component['salary_component_id']
            );

            if (!$definition) {
                continue;
            }

            $amount = $this->calculateComponentAmount(
                $salaryStructure->basic_salary,
                $definition,
                $component
            );

            $salaryStructure->items()->create([
                'salary_component_id' => $definition->id,
                'amount' => $amount,
            ]);
        }
    }

    /**
     * Calculate gross salary.
     *
     * Basic Salary
     * + Earnings
     * - Deductions
     */
    protected function calculateGross(
        $basicSalary,
        array $components
    ): float {
        $basicSalary = (float) $basicSalary;

        $gross = $basicSalary;

        foreach ($components as $component) {

            if (
                empty($component['salary_component_id'])
            ) {
                continue;
            }

            $definition = SalaryComponent::find(
                $component['salary_component_id']
            );

            if (!$definition) {
                continue;
            }

            $amount = $this->calculateComponentAmount(
                $basicSalary,
                $definition,
                $component
            );

            if ($definition->type === 'deduction') {
                $gross -= $amount;
            } else {
                $gross += $amount;
            }
        }

        return round($gross, 2);
    }

    /**
     * Calculate individual component amount.
     *
     * Fixed:
     *      component.value
     *
     * Percentage:
     *      basic salary × component.value / 100
     *
     * For fixed components, the component definition
     * is authoritative.
     *
     * For percentage components, the component definition
     * is also authoritative.
     */
    protected function calculateComponentAmount(
        float $basicSalary,
        SalaryComponent $definition,
        array $submittedComponent = []
    ): float {
        $value = (float) ($definition->value ?? 0);

        if ($definition->calculation_type === 'percentage') {

            $amount = (
                $basicSalary * $value
            ) / 100;

        } else {

            $amount = $value;

        }

        return round($amount, 2);
    }
}
