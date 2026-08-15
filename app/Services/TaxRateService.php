<?php

namespace App\Services;

use App\Models\TaxRate;

class TaxRateService
{
    public function create(array $data): TaxRate
    {
        return TaxRate::create($data);
    }

    public function update(TaxRate $taxRate, array $data): TaxRate
    {
        $taxRate->update($data);

        return $taxRate->fresh();
    }

    public function delete(TaxRate $taxRate): bool
    {
        return $taxRate->delete();
    }
}
