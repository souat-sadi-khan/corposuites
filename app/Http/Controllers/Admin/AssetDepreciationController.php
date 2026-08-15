<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AssetDepreciationController extends Controller
{
    /**
     * Depreciation Calculation — what each asset has depreciated by and
     * what it is now worth on the books.
     *
     * Pure read-only report: no new table/Model/Service/Request. Every
     * input already exists — the capitalised cost comes from Asset
     * Purchase Information, and the method, useful life and salvage
     * percentage come from the asset's category, which has carried them
     * since Asset Categories was built specifically for this module to
     * read. Storing computed figures would only create numbers that drift
     * out of step with those inputs, so they are derived on read, the same
     * "controller only" shape every report in this project uses.
     *
     * Two modes on one page, the shape General Ledger established: with no
     * asset selected it lists every asset's position as of the chosen
     * date; selecting one shows its year-by-year schedule.
     *
     * Assets that cannot be depreciated — no purchase record, a category
     * set to "none", or no useful life — are reported as such rather than
     * quietly omitted or shown as worth nothing.
     */
    public function index(Request $request)
    {
        $assetId = $request->asset_id;
        $categoryId = $request->asset_category_id;
        $asOfDate = $request->as_of_date ? Carbon::parse($request->as_of_date) : Carbon::now();

        $assets = Asset::active()->orderBy('asset_code')->get();
        $categories = AssetCategory::active()->orderBy('name')->get();

        $query = Asset::with(['assetCategory', 'assetPurchase'])->where('status', true);

        if ($categoryId) {
            $query->where('asset_category_id', $categoryId);
        }

        if ($assetId) {
            $query->where('id', $assetId);
        }

        $rows = $query->orderBy('asset_code')->get()
            ->map(fn ($asset) => $this->buildRow($asset, $asOfDate));

        $depreciable = $rows->where('depreciable', true);

        $totals = [
            'assets' => $rows->count(),
            'depreciable' => $depreciable->count(),
            'not_depreciable' => $rows->count() - $depreciable->count(),
            'cost' => round($depreciable->sum('cost'), 2),
            'accumulated' => round($depreciable->sum('accumulated'), 2),
            'book_value' => round($depreciable->sum('book_value'), 2),
        ];

        // A single selected, depreciable asset also gets its full schedule.
        $selectedAsset = null;
        $schedule = null;

        if ($assetId) {
            $selectedAsset = $rows->first();

            if ($selectedAsset && $selectedAsset['depreciable']) {
                $schedule = $this->buildSchedule($selectedAsset);
            }
        }

        return view('admin.asset-depreciation.index', [
            'assets' => $assets,
            'categories' => $categories,
            'assetId' => $assetId,
            'categoryId' => $categoryId,
            'asOfDate' => $asOfDate->toDateString(),
            'rows' => $rows,
            'totals' => $totals,
            'selectedAsset' => $selectedAsset,
            'schedule' => $schedule,
        ]);
    }

    /**
     * One asset's depreciation position as of the cut-off date.
     */
    protected function buildRow(Asset $asset, Carbon $asOfDate): array
    {
        $category = $asset->assetCategory;
        $purchase = $asset->assetPurchase;

        $base = [
            'asset_id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'category' => $category?->name,
            'method' => $category?->depreciation_method,
            'method_label' => $category?->depreciation_method_label,
            'life' => $category?->useful_life_years,
            'salvage_percent' => $category ? (float) $category->salvage_value_percent : 0.0,
            'purchase_date' => $purchase?->purchase_date,
            'cost' => $purchase ? (float) $purchase->total_cost : 0.0,
            'depreciable' => false,
            'reason' => null,
            'annual' => 0.0,
            'accumulated' => 0.0,
            'book_value' => 0.0,
            'fully_depreciated' => false,
        ];

        if (! $purchase) {
            return array_merge($base, ['reason' => 'No purchase information recorded']);
        }

        if (! $category) {
            return array_merge($base, ['reason' => 'Asset has no category']);
        }

        if ($category->depreciation_method === 'none') {
            return array_merge($base, [
                'reason' => 'Category does not depreciate',
                'book_value' => $base['cost'],
            ]);
        }

        if (! $category->useful_life_years) {
            return array_merge($base, ['reason' => 'Category has no useful life set']);
        }

        $cost = $base['cost'];
        $salvage = round($cost * ($base['salvage_percent'] / 100), 2);
        $depreciableAmount = max(0, round($cost - $salvage, 2));
        $life = (int) $category->useful_life_years;

        // Fractional years elapsed since purchase, floored at zero so a
        // future-dated purchase never shows negative depreciation.
        $yearsElapsed = max(0, $purchase->purchase_date->floatDiffInYears($asOfDate));

        [$accumulated, $annual] = $category->depreciation_method === 'reducing_balance'
            ? $this->reducingBalance($cost, $salvage, $life, $yearsElapsed)
            : $this->straightLine($depreciableAmount, $life, $yearsElapsed);

        $accumulated = min($accumulated, $depreciableAmount);

        return array_merge($base, [
            'depreciable' => true,
            'salvage' => $salvage,
            'depreciable_amount' => $depreciableAmount,
            'years_elapsed' => round($yearsElapsed, 2),
            'annual' => round($annual, 2),
            'accumulated' => round($accumulated, 2),
            'book_value' => round($cost - $accumulated, 2),
            'fully_depreciated' => $depreciableAmount > 0 && $accumulated >= $depreciableAmount - 0.005,
        ]);
    }

    /**
     * Straight line: the depreciable amount spread evenly over the life.
     * Returns [accumulated, annual charge].
     */
    protected function straightLine(float $depreciableAmount, int $life, float $yearsElapsed): array
    {
        $annual = $life > 0 ? $depreciableAmount / $life : 0.0;

        return [$annual * $yearsElapsed, $annual];
    }

    /**
     * Reducing balance at the double-declining rate (2 / life), applied to
     * the written-down value each year and floored at the salvage value.
     *
     * The rate convention is stated plainly in the view rather than being
     * configurable: the alternative — deriving a rate from the salvage
     * percentage — is undefined when salvage is zero, which is the common
     * case here, so a single well-known convention is used throughout.
     * Returns [accumulated, first-year charge].
     */
    protected function reducingBalance(float $cost, float $salvage, int $life, float $yearsElapsed): array
    {
        if ($life <= 0) {
            return [0.0, 0.0];
        }

        $rate = 2 / $life;
        $firstYear = $cost * $rate;

        $bookValue = $cost;
        $accumulated = 0.0;
        $remaining = $yearsElapsed;

        while ($remaining > 0) {
            $portion = min(1, $remaining);
            $charge = $bookValue * $rate * $portion;

            // Never depreciate below the salvage value.
            $charge = min($charge, max(0, $bookValue - $salvage));

            if ($charge <= 0) {
                break;
            }

            $accumulated += $charge;
            $bookValue -= $charge;
            $remaining -= $portion;
        }

        return [$accumulated, $firstYear];
    }

    /**
     * Year-by-year schedule for one asset, from the purchase year through
     * the end of its useful life.
     */
    protected function buildSchedule(array $row): Collection
    {
        $cost = $row['cost'];
        $salvage = $row['salvage'] ?? 0.0;
        $life = (int) $row['life'];
        $schedule = collect();

        $bookValue = $cost;
        $accumulated = 0.0;
        $rate = $life > 0 ? 2 / $life : 0.0;
        $annualStraight = $life > 0 ? ($row['depreciable_amount'] ?? 0) / $life : 0.0;

        for ($year = 1; $year <= $life; $year++) {
            $opening = $bookValue;

            $charge = $row['method'] === 'reducing_balance'
                ? $bookValue * $rate
                : $annualStraight;

            $charge = min($charge, max(0, $bookValue - $salvage));
            $charge = round($charge, 2);

            $accumulated = round($accumulated + $charge, 2);
            $bookValue = round($bookValue - $charge, 2);

            $schedule->push([
                'year' => $year,
                'period' => $row['purchase_date']->copy()->addYears($year - 1)->format('M Y')
                    . ' - ' . $row['purchase_date']->copy()->addYears($year)->subDay()->format('M Y'),
                'opening' => round($opening, 2),
                'charge' => $charge,
                'accumulated' => $accumulated,
                'closing' => $bookValue,
            ]);
        }

        return $schedule;
    }
}
