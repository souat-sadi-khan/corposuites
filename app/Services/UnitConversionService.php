<?php

namespace App\Services;

use App\Models\UnitConversion;

class UnitConversionService
{
    public function create(array $data): UnitConversion
    {
        return UnitConversion::create($data);
    }

    public function update(UnitConversion $unitConversion, array $data): UnitConversion
    {
        $unitConversion->update($data);
        return $unitConversion;
    }

    public function delete(UnitConversion $unitConversion): bool
    {
        return $unitConversion->delete();
    }
}
