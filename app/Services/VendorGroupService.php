<?php

namespace App\Services;

use App\Models\VendorGroup;

class VendorGroupService
{
    public function create(array $data): VendorGroup
    {
        return VendorGroup::create($data);
    }

    public function update(VendorGroup $vendorGroup, array $data): VendorGroup
    {
        $vendorGroup->update($data);
        return $vendorGroup;
    }

    public function delete(VendorGroup $vendorGroup): bool
    {
        return $vendorGroup->delete();
    }
}
