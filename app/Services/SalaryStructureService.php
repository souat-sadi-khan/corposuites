<?php

namespace App\Services;

use App\Models\SalaryComponent;
use App\Models\SalaryStructure;

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
