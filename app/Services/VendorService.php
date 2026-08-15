<?php

namespace App\Services;

use App\Models\Vendor;

class VendorService
{
    public function create(array $data): Vendor
    {
        $data['vendor_code'] = $this->generateVendorCode();

        return Vendor::create($data);
    }

    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);
        return $vendor;
    }

    public function delete(Vendor $vendor): bool
    {
        return $vendor->delete();
    }

    protected function generateVendorCode(): string
    {
        $lastId = Vendor::max('id') ?? 0;

        return 'VEN-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
