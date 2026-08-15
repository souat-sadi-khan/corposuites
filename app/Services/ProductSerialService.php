<?php

namespace App\Services;

use App\Models\ProductSerial;

class ProductSerialService
{
    public function create(array $data): ProductSerial
    {
        return ProductSerial::create($data);
    }

    public function update(ProductSerial $productSerial, array $data): ProductSerial
    {
        $productSerial->update($data);

        return $productSerial;
    }

    public function delete(ProductSerial $productSerial): bool
    {
        return $productSerial->delete();
    }
}
