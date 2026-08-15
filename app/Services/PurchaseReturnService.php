<?php

namespace App\Services;

use App\Models\PurchaseReturn;

class PurchaseReturnService
{
    public function create(array $data): PurchaseReturn
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['return_number'] = $this->generateReturnNumber();

        $purchaseReturn = PurchaseReturn::create($data);
        $this->syncItems($purchaseReturn, $items);

        return $purchaseReturn;
    }

    public function update(PurchaseReturn $purchaseReturn, array $data): PurchaseReturn
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $purchaseReturn->update($data);
        $this->syncItems($purchaseReturn, $items);

        return $purchaseReturn;
    }

    public function delete(PurchaseReturn $purchaseReturn): bool
    {
        return $purchaseReturn->delete();
    }

    protected function syncItems(PurchaseReturn $purchaseReturn, array $items): void
    {
        $purchaseReturn->items()->delete();

        foreach ($items as $item) {
            $purchaseReturn->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'condition' => $item['condition'] ?? 'good',
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateReturnNumber(): string
    {
        $lastId = PurchaseReturn::max('id') ?? 0;

        return 'PRET-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
