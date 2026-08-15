<?php

namespace App\Services;

use App\Models\Holiday;

class HolidayService
{
    public function create(array $data): Holiday
    {
        return Holiday::create($data);
    }

    public function update(Holiday $holiday, array $data): Holiday
    {
        $holiday->update($data);
        return $holiday;
    }

    public function delete(Holiday $holiday): bool
    {
        return $holiday->delete();
    }
}
