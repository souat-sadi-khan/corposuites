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

    public function recordPayment(EmployeeLoan $employeeLoan, float $amount): EmployeeLoan
    {
        $newPaid = min($employeeLoan->loan_amount, $employeeLoan->paid_amount + $amount);
        $employeeLoan->update(['paid_amount' => $newPaid]);

        return $employeeLoan;
    }
}
