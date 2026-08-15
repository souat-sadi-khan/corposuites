<?php

namespace App\Services;

use App\Models\FinanceBankAccount;

class FinanceBankAccountService
{
    public function create(array $data): FinanceBankAccount
    {
        return FinanceBankAccount::create($data);
    }

    public function update(FinanceBankAccount $financeBankAccount, array $data): FinanceBankAccount
    {
        $financeBankAccount->update($data);

        return $financeBankAccount;
    }

    public function delete(FinanceBankAccount $financeBankAccount): bool
    {
        return $financeBankAccount->delete();
    }
}
