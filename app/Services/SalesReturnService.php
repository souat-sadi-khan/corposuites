<?php

namespace App\Services;

use App\Models\SalesReturn;

class SalesReturnService
{
    public function create(array $data): SalesReturn
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['return_number'] = $this->generateReturnNumber();

        $salesReturn = SalesReturn::create($data);
        $this->syncItems($salesReturn, $items);

        return $salesReturn;
    }

    public function update(SalesReturn $salesReturn, array $data): SalesReturn
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $salesReturn->update($data);
        $this->syncItems($salesReturn, $items);

        return $salesReturn;
    }

    public function delete(SalesReturn $salesReturn): bool
    {
        return $salesReturn->delete();
    }

    protected function syncItems(SalesReturn $salesReturn, array $items): void
    {
        $salesReturn->items()->delete();

        foreach ($items as $item) {
            $salesReturn->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'condition' => $item['condition'] ?? 'good',
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateReturnNumber(): string
    {
        $lastId = SalesReturn::max('id') ?? 0;
        return 'RET-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
