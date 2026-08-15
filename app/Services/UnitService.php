<?php

namespace App\Services;

use App\Models\Unit;

class UnitService
{
    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);
        return $unit;
    }

    public function delete(Unit $unit): bool
    {
        return $unit->delete();
    }
}
