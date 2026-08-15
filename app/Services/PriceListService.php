<?php

namespace App\Services;

use App\Models\PriceList;

class PriceListService
{
    public function create(array $data): PriceList
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $priceList = PriceList::create($data);
        $this->syncItems($priceList, $items);

        return $priceList;
    }

    public function update(PriceList $priceList, array $data): PriceList
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $priceList->update($data);
        $this->syncItems($priceList, $items);

        return $priceList;
    }

    public function delete(PriceList $priceList): bool
    {
        return $priceList->delete();
    }

    protected function syncItems(PriceList $priceList, array $items): void
    {
        $priceList->items()->delete();

        foreach ($items as $item) {
            $priceList->items()->create([
                'product_id' => $item['product_id'],
                'price' => $item['price'],
            ]);
        }
    }
}
