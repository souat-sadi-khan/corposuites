<?php

namespace App\Services;

use App\Models\Warehouse;

class WarehouseService
{
    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);

        return $warehouse;
    }

    public function delete(Warehouse $warehouse): bool
    {
        return $warehouse->delete();
    }
}
