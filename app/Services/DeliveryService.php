<?php

namespace App\Services;

use App\Models\Delivery;

class DeliveryService
{
    public function create(array $data): Delivery
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['delivery_number'] = $this->generateDeliveryNumber();

        $delivery = Delivery::create($data);
        $this->syncItems($delivery, $items);

        return $delivery;
    }

    public function update(Delivery $delivery, array $data): Delivery
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $delivery->update($data);
        $this->syncItems($delivery, $items);

        return $delivery;
    }

    public function delete(Delivery $delivery): bool
    {
        return $delivery->delete();
    }

    protected function syncItems(Delivery $delivery, array $items): void
    {
        $delivery->items()->delete();

        foreach ($items as $item) {
            $delivery->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }
    }

    protected function generateDeliveryNumber(): string
    {
        $lastId = Delivery::max('id') ?? 0;
        return 'DLV-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
