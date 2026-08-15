<?php

namespace App\Services;

use App\Models\SalaryComponent;

class SalaryComponentService
{
    public function create(array $data): SalaryComponent
    {
        return SalaryComponent::create($data);
    }

    public function update(SalaryComponent $salaryComponent, array $data): SalaryComponent
    {
        $salaryComponent->update($data);
        return $salaryComponent;
    }

    public function delete(SalaryComponent $salaryComponent): bool
    {
        return $salaryComponent->delete();
    }
}
