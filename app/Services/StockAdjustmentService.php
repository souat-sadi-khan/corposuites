<?php

namespace App\Services;

use App\Models\StockAdjustment;

class StockAdjustmentService
{
    public function create(array $data): StockAdjustment
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['adjustment_number'] = $this->generateAdjustmentNumber();

        $stockAdjustment = StockAdjustment::create($data);
        $this->syncItems($stockAdjustment, $items);

        return $stockAdjustment;
    }

    public function update(StockAdjustment $stockAdjustment, array $data): StockAdjustment
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $stockAdjustment->update($data);
        $this->syncItems($stockAdjustment, $items);

        return $stockAdjustment;
    }

    public function delete(StockAdjustment $stockAdjustment): bool
    {
        return $stockAdjustment->delete();
    }

    protected function syncItems(StockAdjustment $stockAdjustment, array $items): void
    {
        $stockAdjustment->items()->delete();

        foreach ($items as $item) {
            $stockAdjustment->items()->create([
                'product_id' => $item['product_id'],
                'adjustment_type' => $item['adjustment_type'] ?? 'increase',
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateAdjustmentNumber(): string
    {
        $lastId = StockAdjustment::max('id') ?? 0;

        return 'ADJ-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
