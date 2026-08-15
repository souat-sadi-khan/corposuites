<?php

namespace App\Services;

use App\Models\PriceTier;

class PriceTierService
{
    public function create(array $data): PriceTier
    {
        return PriceTier::create($data);
    }

    public function update(PriceTier $priceTier, array $data): PriceTier
    {
        $priceTier->update($data);
        return $priceTier;
    }

    public function delete(PriceTier $priceTier): bool
    {
        return $priceTier->delete();
    }
}
