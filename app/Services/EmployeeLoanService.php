<?php

namespace App\Services;

use App\Models\EmployeeLoan;

class EmployeeLoanService
{
    public function create(array $data): EmployeeLoan
    {
        $data['installment_amount'] = round($data['loan_amount'] / $data['installments'], 2);

        return EmployeeLoan::create($data);
    }

    public function update(EmployeeLoan $employeeLoan, array $data): EmployeeLoan
    {
        $data['installment_amount'] = round($data['loan_amount'] / $data['installments'], 2);

        $employeeLoan->update($data);
        return $employeeLoan;
    }

    public function delete(EmployeeLoan $employeeLoan): bool
    {
        return $employeeLoan->delete();
    }

    public function approve(EmployeeLoan $employeeLoan): EmployeeLoan
    {
        $employeeLoan->update(['approval_status' => 'approved']);
        return $employeeLoan;
    }

    public function reject(EmployeeLoan $employeeLoan): EmployeeLoan
    {
        $employeeLoan->update(['approval_status' => 'rejected']);
        return $employeeLoan;
    }

    /**
     * Record a repayment against the loan. $amount may be negative — used
     * by PayrollService::delete() to reverse a payroll-run's automatic
     * deduction — so the result is clamped to [0, loan_amount] on both
     * ends rather than assuming $amount is always a positive addition.
     */
    public function recordPayment(EmployeeLoan $employeeLoan, float $amount): EmployeeLoan
    {
        $newPaid = max(0, min((float) $employeeLoan->loan_amount, (float) $employeeLoan->paid_amount + $amount));
        $employeeLoan->update(['paid_amount' => $newPaid]);

        return $employeeLoan;
    }
}
