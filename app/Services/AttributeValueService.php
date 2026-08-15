<?php

namespace App\Services;

use App\Models\AttributeValue;

class AttributeValueService
{
    public function create(array $data): AttributeValue
    {
        return AttributeValue::create($data);
    }

    public function update(AttributeValue $attributeValue, array $data): AttributeValue
    {
        $attributeValue->update($data);
        return $attributeValue;
    }

    public function delete(AttributeValue $attributeValue): bool
    {
        return $attributeValue->delete();
    }
}
