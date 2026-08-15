<?php

namespace App\Services;

use App\Models\GoodsReceipt;

class GoodsReceiptService
{
    public function create(array $data): GoodsReceipt
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['receipt_number'] = $this->generateReceiptNumber();

        $goodsReceipt = GoodsReceipt::create($data);
        $this->syncItems($goodsReceipt, $items);

        return $goodsReceipt;
    }

    public function update(GoodsReceipt $goodsReceipt, array $data): GoodsReceipt
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $goodsReceipt->update($data);
        $this->syncItems($goodsReceipt, $items);

        return $goodsReceipt;
    }

    public function delete(GoodsReceipt $goodsReceipt): bool
    {
        return $goodsReceipt->delete();
    }

    protected function syncItems(GoodsReceipt $goodsReceipt, array $items): void
    {
        $goodsReceipt->items()->delete();

        foreach ($items as $item) {
            $goodsReceipt->items()->create([
                'product_id' => $item['product_id'],
                'quantity_received' => $item['quantity_received'],
                'condition' => $item['condition'] ?? 'good',
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateReceiptNumber(): string
    {
        $lastId = GoodsReceipt::max('id') ?? 0;

        return 'GRN-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
