<?php

namespace App\Services;

use App\Models\StockEntry;

class StockEntryService
{
    public function create(array $data): StockEntry
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['entry_number'] = $this->generateEntryNumber();

        $stockEntry = StockEntry::create($data);
        $this->syncItems($stockEntry, $items);

        return $stockEntry;
    }

    public function update(StockEntry $stockEntry, array $data): StockEntry
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $stockEntry->update($data);
        $this->syncItems($stockEntry, $items);

        return $stockEntry;
    }

    public function delete(StockEntry $stockEntry): bool
    {
        return $stockEntry->delete();
    }

    protected function syncItems(StockEntry $stockEntry, array $items): void
    {
        $stockEntry->items()->delete();

        foreach ($items as $item) {
            $stockEntry->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateEntryNumber(): string
    {
        $lastId = StockEntry::max('id') ?? 0;

        return 'SE-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
