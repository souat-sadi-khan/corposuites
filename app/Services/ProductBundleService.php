<?php

namespace App\Services;

use App\Models\ProductBundle;

class ProductBundleService
{
    public function create(array $data): ProductBundle
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $productBundle = ProductBundle::create($data);
        $this->syncItems($productBundle, $items);

        return $productBundle;
    }

    public function update(ProductBundle $productBundle, array $data): ProductBundle
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $productBundle->update($data);
        $this->syncItems($productBundle, $items);

        return $productBundle;
    }

    public function delete(ProductBundle $productBundle): bool
    {
        return $productBundle->delete();
    }

    protected function syncItems(ProductBundle $productBundle, array $items): void
    {
        $productBundle->items()->delete();

        foreach ($items as $item) {
            $productBundle->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }
    }
}
