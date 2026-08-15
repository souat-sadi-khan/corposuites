<?php

namespace App\Services;

use App\Models\ProductPrice;

class ProductPriceService
{
    public function create(array $data): ProductPrice
    {
        return ProductPrice::create($data);
    }

    public function update(ProductPrice $productPrice, array $data): ProductPrice
    {
        $productPrice->update($data);
        return $productPrice;
    }

    public function delete(ProductPrice $productPrice): bool
    {
        return $productPrice->delete();
    }
}
