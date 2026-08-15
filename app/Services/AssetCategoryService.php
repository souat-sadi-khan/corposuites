<?php

namespace App\Services;

use App\Models\AssetCategory;

class AssetCategoryService
{
    public function create(array $data): AssetCategory
    {
        return AssetCategory::create($data);
    }

    public function update(AssetCategory $assetCategory, array $data): AssetCategory
    {
        $assetCategory->update($data);

        return $assetCategory->fresh();
    }

    public function delete(AssetCategory $assetCategory): bool
    {
        return $assetCategory->delete();
    }
}
