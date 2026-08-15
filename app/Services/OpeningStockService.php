<?php

namespace App\Services;

use App\Models\OpeningStock;

class OpeningStockService
{
    public function create(array $data): OpeningStock
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['entry_number'] = $this->generateEntryNumber();

        $openingStock = OpeningStock::create($data);
        $this->syncItems($openingStock, $items);

        return $openingStock;
    }

    public function update(OpeningStock $openingStock, array $data): OpeningStock
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $openingStock->update($data);
        $this->syncItems($openingStock, $items);

        return $openingStock;
    }

    public function delete(OpeningStock $openingStock): bool
    {
        return $openingStock->delete();
    }

    protected function syncItems(OpeningStock $openingStock, array $items): void
    {
        $openingStock->items()->delete();

        foreach ($items as $item) {
            $openingStock->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateEntryNumber(): string
    {
        $lastId = OpeningStock::max('id') ?? 0;

        return 'OS-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
