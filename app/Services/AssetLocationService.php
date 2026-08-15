<?php

namespace App\Services;

use App\Models\AssetLocation;

class AssetLocationService
{
    public function create(array $data): AssetLocation
    {
        return AssetLocation::create($data);
    }

    public function update(AssetLocation $assetLocation, array $data): AssetLocation
    {
        $assetLocation->update($data);

        return $assetLocation->fresh();
    }

    public function delete(AssetLocation $assetLocation): bool
    {
        return $assetLocation->delete();
    }
}
