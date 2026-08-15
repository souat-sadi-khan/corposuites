<?php

namespace App\Services;

use App\Models\Attendance;

class AttendanceService
{
    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    public function update(Attendance $attendance, array $data): Attendance
    {
        $attendance->update($data);
        return $attendance;
    }

    public function delete(Attendance $attendance): bool
    {
        return $attendance->delete();
    }
}
