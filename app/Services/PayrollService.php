<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\SalaryStructure;

class PayrollService
{
    public function create(array $data): Payroll
    {
        $structure = SalaryStructure::where('employee_id', $data['employee_id'])
            ->active()
            ->orderByDesc('effective_date')
            ->with('items.salaryComponent')
            ->first();

        $totals = $this->buildTotals($structure);

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

    protected function buildTotals(?SalaryStructure $structure): array
    {
        if (!$structure) {
            return ['basic_salary' => 0, 'total_earnings' => 0, 'total_deductions' => 0, 'net_salary' => 0];
        }

        $earnings = (float) $structure->basic_salary;
        $deductions = 0;

        foreach ($structure->items as $item) {
            $amount = (float) $item->amount;
            $value = $item->salaryComponent->calculation_type === 'percentage'
                ? $structure->basic_salary * ($amount / 100)
                : $amount;

            if ($item->salaryComponent->type === 'deduction') {
                $deductions += $value;
            } else {
                $earnings += $value;
            }
        }

        return [
            'basic_salary' => round($structure->basic_salary, 2),
            'total_earnings' => round($earnings, 2),
            'total_deductions' => round($deductions, 2),
            'net_salary' => round($earnings - $deductions, 2),
        ];
    }
}
