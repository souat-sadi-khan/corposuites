<?php

namespace App\Services;

use App\Models\AccountType;

class AccountTypeService
{
    public function create(array $data): AccountType
    {
        return AccountType::create($data);
    }

    public function update(AccountType $accountType, array $data): AccountType
    {
        $accountType->update($data);

        return $accountType;
    }

    public function delete(AccountType $accountType): bool
    {
        return $accountType->delete();
    }
}
