<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\SalaryTemplate;

class SalaryTemplateService
{
    public function __construct(
        private SalaryStructureService $salaryStructures,
        private MinimumWageComplianceService $minimumWageCompliance
    ) {
    }

    public function create(array $data): SalaryTemplate
    {
        $components = $data['components'] ?? [];

        unset($data['components']);

        $data['gross_salary'] = $this->calculateGross(
            $data['basic_salary'],
            $components
        );

        $salaryTemplate = SalaryTemplate::create($data);

        $this->syncItems($salaryTemplate, $components);

        return $salaryTemplate;
    }

    public function update(SalaryTemplate $salaryTemplate, array $data): SalaryTemplate
    {
        $components = $data['components'] ?? [];

        unset($data['components']);

        $data['gross_salary'] = $this->calculateGross(
            $data['basic_salary'],
            $components
        );

        $salaryTemplate->update($data);

        $this->syncItems($salaryTemplate, $components);

        return $salaryTemplate;
    }

    public function delete(SalaryTemplate $salaryTemplate): bool
    {
        return $salaryTemplate->delete();
    }

    /**
     * Create one Salary Structure per selected employee, carrying over the
     * template's pay type, rate, and components exactly — this is what makes
     * bulk assignment possible (e.g. "these 10 employees are all on the same
     * salary"). Goes through SalaryStructureService::create() so every rule
     * that already applies to a manually-created structure (component amount
     * recalculation against each employee's basic_salary, etc.) applies here
     * too, rather than duplicating that logic.
     *
     * An employee whose location's configured minimum wage exceeds the
     * template's rate is skipped rather than aborting the whole batch —
     * there is no per-employee form here to surface a validation error
     * against, so this is the bulk-action equivalent of
     * SalaryStructureService::assignComponentToEmployees()'s own
     * {updated, skipped} shape.
     *
     * @return array{created: \Illuminate\Support\Collection<int, \App\Models\SalaryStructure>, skipped: \Illuminate\Support\Collection<int, array>}
     */
    public function assignToEmployees(
        SalaryTemplate $salaryTemplate,
        array $employeeIds,
        string $effectiveDate,
        bool $status
    ): array {
        $components = $salaryTemplate->items->map(fn ($item) => [
            'salary_component_id' => $item->salary_component_id,
            'amount' => $item->amount,
        ])->all();

        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

        $created = collect();
        $skipped = collect();

        foreach ($employeeIds as $employeeId) {
            $employee = $employees->get($employeeId);

            $violation = $employee
                ? $this->minimumWageCompliance->violationMessage(
                    $employee,
                    $salaryTemplate->pay_type,
                    (float) $salaryTemplate->basic_salary
                )
                : null;

            if ($violation) {
                $skipped->push([
                    'employee_id' => (int) $employeeId,
                    'reason' => $violation,
                ]);

                continue;
            }

            $created->push($this->salaryStructures->create([
                'employee_id' => $employeeId,
                'pay_type' => $salaryTemplate->pay_type,
                'effective_date' => $effectiveDate,
                'basic_salary' => $salaryTemplate->basic_salary,
                'status' => $status,
                'components' => $components,
            ]));
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * Sync template components.
     *
     * The amount stored in salary_template_items
     * is always the final calculated amount.
     */
    protected function syncItems(SalaryTemplate $salaryTemplate, array $components): void
    {
        $salaryTemplate->items()->delete();

        foreach ($components as $component) {

            if (empty($component['salary_component_id'])) {
                continue;
            }

            $definition = SalaryComponent::find($component['salary_component_id']);

            if (!$definition) {
                continue;
            }

            $amount = $this->calculateComponentAmount(
                $salaryTemplate->basic_salary,
                $definition,
                $component
            );

            $salaryTemplate->items()->create([
                'salary_component_id' => $definition->id,
                'amount' => $amount,
            ]);
        }
    }

    protected function calculateGross($basicSalary, array $components): float
    {
        $basicSalary = (float) $basicSalary;
        $gross = $basicSalary;

        foreach ($components as $component) {

            if (empty($component['salary_component_id'])) {
                continue;
            }

            $definition = SalaryComponent::find($component['salary_component_id']);

            if (!$definition) {
                continue;
            }

            $amount = $this->calculateComponentAmount($basicSalary, $definition, $component);

            if ($definition->type === 'deduction') {
                $gross -= $amount;
            } else {
                $gross += $amount;
            }
        }

        return round($gross, 2);
    }

    protected function calculateComponentAmount(
        float $basicSalary,
        SalaryComponent $definition,
        array $submittedComponent = []
    ): float {
        $value = (float) ($definition->value ?? 0);

        $amount = $definition->calculation_type === 'percentage'
            ? ($basicSalary * $value) / 100
            : $value;

        return round($amount, 2);
    }
}
