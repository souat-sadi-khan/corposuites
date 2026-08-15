<?php

namespace App\Services;

use App\Models\ProductVariant;

class ProductVariantService
{
    public function create(array $data): ProductVariant
    {
        $attributeValueIds = $data['attribute_value_ids'];
        unset($data['attribute_value_ids']);

        $variant = ProductVariant::create($data);
        $variant->attributeValues()->sync($attributeValueIds);

        return $variant;
    }

    public function update(ProductVariant $productVariant, array $data): ProductVariant
    {
        $attributeValueIds = $data['attribute_value_ids'];
        unset($data['attribute_value_ids']);

        $productVariant->update($data);
        $productVariant->attributeValues()->sync($attributeValueIds);

        return $productVariant;
    }

    public function delete(ProductVariant $productVariant): bool
    {
        return $productVariant->delete();
    }
}
