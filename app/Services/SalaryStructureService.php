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

        $data['gross_salary'] = $this->calculateGross($data['basic_salary'], $components);

        $salaryStructure = SalaryStructure::create($data);
        $this->syncItems($salaryStructure, $components);

        return $salaryStructure;
    }

    public function update(SalaryStructure $salaryStructure, array $data): SalaryStructure
    {
        $components = $data['components'] ?? [];
        unset($data['components']);

        $data['gross_salary'] = $this->calculateGross($data['basic_salary'], $components);

        $salaryStructure->update($data);
        $this->syncItems($salaryStructure, $components);

        return $salaryStructure;
    }

    public function delete(SalaryStructure $salaryStructure): bool
    {
        return $salaryStructure->delete();
    }

    protected function syncItems(SalaryStructure $salaryStructure, array $components): void
    {
        $salaryStructure->items()->delete();

        foreach ($components as $component) {
            $salaryStructure->items()->create([
                'salary_component_id' => $component['salary_component_id'],
                'amount' => $component['amount'],
            ]);
        }
    }

    protected function calculateGross($basicSalary, array $components): float
    {
        $gross = (float) $basicSalary;

        foreach ($components as $component) {
            $definition = SalaryComponent::find($component['salary_component_id']);
            $amount = (float) $component['amount'];

            if (!$definition) {
                continue;
            }

            $value = $definition->calculation_type === 'percentage'
                ? $basicSalary * ($amount / 100)
                : $amount;

            $gross += $definition->type === 'deduction' ? -$value : $value;
        }

        return round($gross, 2);
    }
}
