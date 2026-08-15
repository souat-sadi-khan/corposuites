<?php

namespace App\Services;

use App\Models\StockCount;

class StockCountService
{
    public function create(array $data): StockCount
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['count_number'] = $this->generateCountNumber();

        $stockCount = StockCount::create($data);
        $this->syncItems($stockCount, $items);

        return $stockCount;
    }

    public function update(StockCount $stockCount, array $data): StockCount
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $stockCount->update($data);
        $this->syncItems($stockCount, $items);

        return $stockCount;
    }

    public function delete(StockCount $stockCount): bool
    {
        return $stockCount->delete();
    }

    protected function syncItems(StockCount $stockCount, array $items): void
    {
        $stockCount->items()->delete();

        foreach ($items as $item) {
            $stockCount->items()->create([
                'product_id' => $item['product_id'],
                'system_quantity' => $item['system_quantity'] ?? null,
                'counted_quantity' => $item['counted_quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateCountNumber(): string
    {
        $lastId = StockCount::max('id') ?? 0;

        return 'SC-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
