<?php

namespace App\Services;

use App\Models\Shift;

class ShiftService
{
    public function create(array $data): Shift
    {
        return Shift::create($data);
    }

    public function update(Shift $shift, array $data): Shift
    {
        $shift->update($data);
        return $shift;
    }

    public function delete(Shift $shift): bool
    {
        return $shift->delete();
    }
}
