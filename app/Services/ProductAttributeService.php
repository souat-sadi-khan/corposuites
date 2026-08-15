<?php

namespace App\Services;

use App\Models\ProductAttribute;

class ProductAttributeService
{
    public function create(array $data): ProductAttribute
    {
        return ProductAttribute::create($data);
    }

    public function update(ProductAttribute $productAttribute, array $data): ProductAttribute
    {
        $productAttribute->update($data);
        return $productAttribute;
    }

    public function delete(ProductAttribute $productAttribute): bool
    {
        return $productAttribute->delete();
    }
}
