<?php

namespace App\Services;

use App\Models\ProductBatch;

class ProductBatchService
{
    public function create(array $data): ProductBatch
    {
        return ProductBatch::create($data);
    }

    public function update(ProductBatch $productBatch, array $data): ProductBatch
    {
        $productBatch->update($data);

        return $productBatch;
    }

    public function delete(ProductBatch $productBatch): bool
    {
        return $productBatch->delete();
    }
}
