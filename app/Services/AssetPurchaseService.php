<?php

namespace App\Services;

use App\Models\AssetPurchase;

class AssetPurchaseService
{
    public function create(array $data): AssetPurchase
    {
        return AssetPurchase::create($data);
    }

    public function update(AssetPurchase $assetPurchase, array $data): AssetPurchase
    {
        $assetPurchase->update($data);

        return $assetPurchase->fresh();
    }

    public function delete(AssetPurchase $assetPurchase): bool
    {
        return $assetPurchase->delete();
    }
}
