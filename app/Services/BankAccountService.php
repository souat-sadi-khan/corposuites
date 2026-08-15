<?php

namespace App\Services;

use App\Models\BankAccount;

class BankAccountService
{
    public function create(array $data): BankAccount
    {
        if (!empty($data['is_primary'])) {
            $this->clearPrimary($data['employee_id']);
        }

        return BankAccount::create($data);
    }

    public function update(BankAccount $bankAccount, array $data): BankAccount
    {
        if (!empty($data['is_primary'])) {
            $this->clearPrimary($data['employee_id'], $bankAccount->id);
        }

        $bankAccount->update($data);
        return $bankAccount;
    }

    public function delete(BankAccount $bankAccount): bool
    {
        return $bankAccount->delete();
    }

    protected function clearPrimary(int $employeeId, ?int $exceptId = null): void
    {
        BankAccount::where('employee_id', $employeeId)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_primary' => false]);
    }
}
