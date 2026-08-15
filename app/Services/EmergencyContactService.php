<?php

namespace App\Services;

use App\Models\EmergencyContact;

class EmergencyContactService
{
    public function create(array $data): EmergencyContact
    {
        if (!empty($data['is_primary'])) {
            $this->clearPrimary($data['employee_id']);
        }

        return EmergencyContact::create($data);
    }

    public function update(EmergencyContact $emergencyContact, array $data): EmergencyContact
    {
        if (!empty($data['is_primary'])) {
            $this->clearPrimary($data['employee_id'], $emergencyContact->id);
        }

        $emergencyContact->update($data);
        return $emergencyContact;
    }

    public function delete(EmergencyContact $emergencyContact): bool
    {
        return $emergencyContact->delete();
    }

    protected function clearPrimary(int $employeeId, ?int $exceptId = null): void
    {
        EmergencyContact::where('employee_id', $employeeId)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_primary' => false]);
    }
}
