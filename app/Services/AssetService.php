<?php

namespace App\Services;

use App\Models\Asset;

class AssetService
{
    public function create(array $data): Asset
    {
        $data['asset_code'] = $this->generateAssetCode();

        return Asset::create($data);
    }

    public function update(Asset $asset, array $data): Asset
    {
        // asset_code is issued once at registration and never re-issued —
        // it is the tag physically attached to the item.
        unset($data['asset_code']);

        $asset->update($data);

        return $asset->fresh();
    }

    public function delete(Asset $asset): bool
    {
        return $asset->delete();
    }

    /**
     * Sequential asset tag, same numbering technique every other
     * server-generated reference in this project uses (CUST-/VEN-/JE-/…).
     */
    protected function generateAssetCode(): string
    {
        return 'AST-' . str_pad((int) Asset::max('id') + 1, 6, '0', STR_PAD_LEFT);
    }
}
