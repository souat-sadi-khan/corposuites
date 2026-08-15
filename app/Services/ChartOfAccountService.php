<?php

namespace App\Services;

use App\Models\ChartOfAccount;

class ChartOfAccountService
{
    public function create(array $data): ChartOfAccount
    {
        return ChartOfAccount::create($data);
    }

    public function update(ChartOfAccount $chartOfAccount, array $data): ChartOfAccount
    {
        $chartOfAccount->update($data);

        return $chartOfAccount;
    }

    public function delete(ChartOfAccount $chartOfAccount): bool
    {
        return $chartOfAccount->delete();
    }
}
