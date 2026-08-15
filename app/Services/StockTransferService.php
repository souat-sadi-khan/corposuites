<?php

namespace App\Services;

use App\Models\StockTransfer;

class StockTransferService
{
    public function create(array $data): StockTransfer
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['transfer_number'] = $this->generateTransferNumber();

        $stockTransfer = StockTransfer::create($data);
        $this->syncItems($stockTransfer, $items);

        return $stockTransfer;
    }

    public function update(StockTransfer $stockTransfer, array $data): StockTransfer
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $stockTransfer->update($data);
        $this->syncItems($stockTransfer, $items);

        return $stockTransfer;
    }

    public function delete(StockTransfer $stockTransfer): bool
    {
        return $stockTransfer->delete();
    }

    protected function syncItems(StockTransfer $stockTransfer, array $items): void
    {
        $stockTransfer->items()->delete();

        foreach ($items as $item) {
            $stockTransfer->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateTransferNumber(): string
    {
        $lastId = StockTransfer::max('id') ?? 0;

        return 'ST-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
