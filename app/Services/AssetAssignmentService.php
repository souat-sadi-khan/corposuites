<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAssignment;

class AssetAssignmentService
{
    public function create(array $data): AssetAssignment
    {
        $assignment = AssetAssignment::create($data);

        $this->syncAssetState($assignment);

        return $assignment;
    }

    public function update(AssetAssignment $assetAssignment, array $data): AssetAssignment
    {
        $previousAssetId = $assetAssignment->asset_id;

        $assetAssignment->update($data);
        $updated = $assetAssignment->fresh();

        // If the assignment was moved to a different asset, the one it left
        // must be released as well as the new one claimed.
        if ((int) $previousAssetId !== (int) $updated->asset_id) {
            $this->releaseIfUnassigned($previousAssetId);
        }

        $this->syncAssetState($updated);

        return $updated;
    }

    public function delete(AssetAssignment $assetAssignment): bool
    {
        $assetId = $assetAssignment->asset_id;

        $deleted = $assetAssignment->delete();

        $this->releaseIfUnassigned($assetId);

        return $deleted;
    }

    /**
     * Keep `assets.asset_status` truthful as assignments are made, returned
     * or cancelled — the same "one module's service maintains a status
     * column another module owns" pattern already used by
     * `SupplierQuotationService::markRfqVendorResponded()` and
     * `BankReconciliationService`'s reconciled-flag handling.
     *
     * A disposed or under-maintenance asset is left alone: those states are
     * set deliberately elsewhere and must not be silently overwritten by an
     * assignment record.
     */
    protected function syncAssetState(AssetAssignment $assignment): void
    {
        $asset = Asset::find($assignment->asset_id);

        if (! $asset || in_array($asset->asset_status, ['disposed', 'under_maintenance'], true)) {
            return;
        }

        if ($assignment->assignment_status === 'assigned') {
            $asset->update(['asset_status' => 'in_use']);

            return;
        }

        $this->releaseIfUnassigned($asset->id);
    }

    /**
     * Put an asset back in store, but only once nothing else still holds it.
     */
    protected function releaseIfUnassigned(?int $assetId): void
    {
        if (! $assetId) {
            return;
        }

        $asset = Asset::find($assetId);

        if (! $asset || in_array($asset->asset_status, ['disposed', 'under_maintenance'], true)) {
            return;
        }

        $stillAssigned = AssetAssignment::where('asset_id', $assetId)
            ->where('assignment_status', 'assigned')
            ->exists();

        if (! $stillAssigned) {
            $asset->update(['asset_status' => 'in_store']);
        }
    }
}
