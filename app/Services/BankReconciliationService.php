<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankTransaction;

class BankReconciliationService
{
    public function create(array $data): BankReconciliation
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['reconciliation_number'] = $this->generateReconciliationNumber();
        $data = array_merge($data, $this->calculateTotals($data['statement_opening_balance'], $data['statement_closing_balance'], $items));

        $bankReconciliation = BankReconciliation::create($data);
        $this->syncItems($bankReconciliation, $items);

        return $bankReconciliation;
    }

    public function update(BankReconciliation $bankReconciliation, array $data): BankReconciliation
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data = array_merge($data, $this->calculateTotals($data['statement_opening_balance'], $data['statement_closing_balance'], $items));

        $bankReconciliation->update($data);
        $this->syncItems($bankReconciliation, $items);

        return $bankReconciliation;
    }

    public function delete(BankReconciliation $bankReconciliation): bool
    {
        // Unmark every transaction this reconciliation had claimed before removing it.
        $this->unmarkTransactions($bankReconciliation->items()->pluck('bank_transaction_id')->all());

        return $bankReconciliation->delete();
    }

    /**
     * Computed closing balance = statement opening balance plus every included
     * transaction (deposits add, withdrawals subtract). Variance is the
     * difference against the statement's own claimed closing balance — zero
     * when the reconciliation is fully accounted for.
     */
    protected function calculateTotals($openingBalance, $closingBalance, array $items): array
    {
        $transactions = BankTransaction::whereIn('id', collect($items)->pluck('bank_transaction_id'))->get()->keyBy('id');

        $runningBalance = (float) $openingBalance;

        foreach ($items as $item) {
            $transaction = $transactions->get($item['bank_transaction_id']);
            if (!$transaction) {
                continue;
            }

            $runningBalance += $transaction->transaction_type === 'withdrawal' ? -(float) $transaction->amount : (float) $transaction->amount;
        }

        $computedClosingBalance = round($runningBalance, 2);

        return [
            'computed_closing_balance' => $computedClosingBalance,
            'variance' => round((float) $closingBalance - $computedClosingBalance, 2),
        ];
    }

    /**
     * Delete-then-recreate the membership rows, and mark/unmark the referenced
     * BankTransaction.reconciled flag to match: transactions newly included in
     * this reconciliation are marked reconciled as of the statement date;
     * transactions that were previously included but are no longer in the
     * submitted list are unmarked back to unreconciled.
     */
    protected function syncItems(BankReconciliation $bankReconciliation, array $items): void
    {
        $previousTransactionIds = $bankReconciliation->items()->pluck('bank_transaction_id')->all();

        $bankReconciliation->items()->delete();

        $newTransactionIds = [];

        foreach ($items as $item) {
            $bankReconciliation->items()->create([
                'bank_transaction_id' => $item['bank_transaction_id'],
            ]);
            $newTransactionIds[] = $item['bank_transaction_id'];
        }

        $removedTransactionIds = array_diff($previousTransactionIds, $newTransactionIds);
        $this->unmarkTransactions($removedTransactionIds);

        BankTransaction::whereIn('id', $newTransactionIds)->update([
            'reconciled' => true,
            'reconciled_date' => $bankReconciliation->statement_date,
        ]);
    }

    protected function unmarkTransactions(array $transactionIds): void
    {
        if (empty($transactionIds)) {
            return;
        }

        BankTransaction::whereIn('id', $transactionIds)->update([
            'reconciled' => false,
            'reconciled_date' => null,
        ]);
    }

    protected function generateReconciliationNumber(): string
    {
        $lastId = BankReconciliation::max('id') ?? 0;

        return 'BR-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
