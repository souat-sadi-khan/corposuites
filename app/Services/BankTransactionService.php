<?php

namespace App\Services;

use App\Models\BankTransaction;

class BankTransactionService
{
    public function create(array $data): BankTransaction
    {
        return BankTransaction::create($data);
    }

    public function update(BankTransaction $bankTransaction, array $data): BankTransaction
    {
        $bankTransaction->update($data);

        return $bankTransaction;
    }

    public function delete(BankTransaction $bankTransaction): bool
    {
        return $bankTransaction->delete();
    }
}
