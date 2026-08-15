<?php

namespace App\Services;

use App\Models\CustomerGroup;

class CustomerGroupService
{
    public function create(array $data): CustomerGroup
    {
        return CustomerGroup::create($data);
    }

    public function update(CustomerGroup $customerGroup, array $data): CustomerGroup
    {
        $customerGroup->update($data);
        return $customerGroup;
    }

    public function delete(CustomerGroup $customerGroup): bool
    {
        return $customerGroup->delete();
    }
}
