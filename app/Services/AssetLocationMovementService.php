<?php

namespace App\Services;

use App\Models\AssetLocationMovement;

class AssetLocationMovementService
{
    public function create(array $data): AssetLocationMovement
    {
        return AssetLocationMovement::create($data);
    }

    public function update(AssetLocationMovement $movement, array $data): AssetLocationMovement
    {
        $movement->update($data);

        return $movement->fresh();
    }

    public function delete(AssetLocationMovement $movement): bool
    {
        return $movement->delete();
    }
}
