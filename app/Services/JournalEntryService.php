<?php

namespace App\Services;

use App\Models\JournalEntry;

class JournalEntryService
{
    public function create(array $data): JournalEntry
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['entry_number'] = $this->generateEntryNumber();
        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $journalEntry = JournalEntry::create($data);
        $this->syncItems($journalEntry, $items);

        return $journalEntry;
    }

    public function update(JournalEntry $journalEntry, array $data): JournalEntry
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $journalEntry->update($data);
        $this->syncItems($journalEntry, $items);

        return $journalEntry;
    }

    public function delete(JournalEntry $journalEntry): bool
    {
        return $journalEntry->delete();
    }

    protected function syncItems(JournalEntry $journalEntry, array $items): void
    {
        $journalEntry->items()->delete();

        foreach ($items as $item) {
            $journalEntry->items()->create([
                'chart_of_account_id' => $item['chart_of_account_id'],
                'debit' => (float) ($item['debit'] ?? 0),
                'credit' => (float) ($item['credit'] ?? 0),
                'description' => $item['description'] ?? null,
            ]);
        }
    }

    protected function calculateTotals(array $items): array
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($items as $item) {
            $totalDebit += (float) ($item['debit'] ?? 0);
            $totalCredit += (float) ($item['credit'] ?? 0);
        }

        return [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    protected function generateEntryNumber(): string
    {
        $lastId = JournalEntry::max('id') ?? 0;
        return 'JE-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
