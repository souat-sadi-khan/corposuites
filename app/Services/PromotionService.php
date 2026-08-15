<?php

namespace App\Services;

use App\Models\Designation;
use App\Models\Employee;
use App\Models\Promotion;

class PromotionService
{
    public function create(array $data): Promotion
    {
        $employee = Employee::with('designation')->find($data['employee_id']);

        if ($employee) {
            $data['from_designation'] = $data['from_designation'] ?: $employee->designation?->name;

            $toDesignation = Designation::where('name', $data['to_designation'])->first();

            if ($toDesignation) {
                $employee->update(['designation_id' => $toDesignation->id]);
            }
        }

        return Promotion::create($data);
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->update($data);
        return $promotion;
    }

    public function delete(Promotion $promotion): bool
    {
        return $promotion->delete();
    }
}
