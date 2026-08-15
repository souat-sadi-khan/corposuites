<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDisposal;
use Illuminate\Support\Carbon;

class AssetDisposalService
{
    public function create(array $data): AssetDisposal
    {
        $data = $this->withValuation($data);

        $disposal = AssetDisposal::create($data);

        $this->syncAssetState($disposal);

        return $disposal;
    }

    public function update(AssetDisposal $disposal, array $data): AssetDisposal
    {
        $previousAssetId = $disposal->asset_id;

        $data = $this->withValuation($data);

        $disposal->update($data);
        $updated = $disposal->fresh();

        // Moved to a different asset: the one it left goes back into use.
        if ((int) $previousAssetId !== (int) $updated->asset_id) {
            $this->restoreAsset($previousAssetId);
        }

        $this->syncAssetState($updated);

        return $updated;
    }

    public function delete(AssetDisposal $disposal): bool
    {
        $assetId = $disposal->asset_id;

        $deleted = $disposal->delete();

        $this->restoreAsset($assetId);

        return $deleted;
    }

    /**
     * Snapshot the asset's book value at the disposal date and the
     * resulting gain or loss.
     *
     * These are stored rather than derived precisely because a disposal is
     * a historical financial event: correcting an asset's cost or its
     * category's useful life next year must not silently rewrite what a
     * sale two years ago was worth. That is the same reasoning behind
     * Sales Commission storing its figures while Sales Target computes
     * them live.
     *
     * The book value follows the same conventions the Depreciation
     * Calculation module documents — straight line over `cost - salvage`,
     * or double-declining (2 / life) floored at salvage — recomputed here
     * rather than shared, consistent with this project's established rule
     * that each module owns its own calculation.
     */
    protected function withValuation(array $data): array
    {
        $bookValue = $this->bookValueAt(
            (int) ($data['asset_id'] ?? 0),
            $data['disposal_date'] ?? null
        );

        $proceeds = (float) ($data['proceeds'] ?? 0);

        $data['book_value_at_disposal'] = $bookValue;
        $data['gain_loss'] = round($proceeds - $bookValue, 2);

        return $data;
    }

    /**
     * Written-down value of an asset on a given date. Falls back to the
     * capitalised cost when the asset does not depreciate, and to zero
     * when there is no purchase record to value it from.
     */
    public function bookValueAt(int $assetId, ?string $onDate): float
    {
        $asset = Asset::with(['assetCategory', 'assetPurchase'])->find($assetId);

        if (! $asset || ! $asset->assetPurchase) {
            return 0.0;
        }

        $cost = (float) $asset->assetPurchase->total_cost;
        $category = $asset->assetCategory;

        if (! $category || $category->depreciation_method === 'none' || ! $category->useful_life_years) {
            return round($cost, 2);
        }

        $date = $onDate ? Carbon::parse($onDate) : Carbon::now();
        $years = max(0, $asset->assetPurchase->purchase_date->floatDiffInYears($date));

        $life = (int) $category->useful_life_years;
        $salvage = round($cost * ((float) $category->salvage_value_percent / 100), 2);

        if ($category->depreciation_method === 'reducing_balance') {
            $rate = 2 / $life;
            $bookValue = $cost;
            $remaining = $years;

            while ($remaining > 0) {
                $portion = min(1, $remaining);
                $charge = min($bookValue * $rate * $portion, max(0, $bookValue - $salvage));

                if ($charge <= 0) {
                    break;
                }

                $bookValue -= $charge;
                $remaining -= $portion;
            }

            return round($bookValue, 2);
        }

        $depreciable = max(0, $cost - $salvage);
        $accumulated = min($depreciable, ($depreciable / $life) * $years);

        return round($cost - $accumulated, 2);
    }

    /**
     * A completed disposal takes the asset out of service; anything else
     * leaves it in use.
     */
    protected function syncAssetState(AssetDisposal $disposal): void
    {
        if ($disposal->disposal_status === 'completed') {
            Asset::where('id', $disposal->asset_id)->update(['asset_status' => 'disposed']);

            return;
        }

        $this->restoreAsset($disposal->asset_id);
    }

    /**
     * Bring an asset back from disposed. It returns to store rather than
     * to whatever state it held before — that earlier state is not
     * recorded anywhere, and guessing would be worse than a known-safe
     * default. Assets not currently marked disposed are left untouched.
     */
    protected function restoreAsset(?int $assetId): void
    {
        if (! $assetId) {
            return;
        }

        $asset = Asset::find($assetId);

        if (! $asset || $asset->asset_status !== 'disposed') {
            return;
        }

        $stillDisposed = AssetDisposal::where('asset_id', $assetId)
            ->where('disposal_status', 'completed')
            ->exists();

        if (! $stillDisposed) {
            $asset->update(['asset_status' => 'in_store']);
        }
    }
}
