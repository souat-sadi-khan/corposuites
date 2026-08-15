<?php

namespace App\Services;

use App\Models\WarehouseLocation;

class WarehouseLocationService
{
    public function create(array $data): WarehouseLocation
    {
        return WarehouseLocation::create($data);
    }

    public function update(WarehouseLocation $warehouseLocation, array $data): WarehouseLocation
    {
        $warehouseLocation->update($data);

        return $warehouseLocation;
    }

    public function delete(WarehouseLocation $warehouseLocation): bool
    {
        return $warehouseLocation->delete();
    }
}
