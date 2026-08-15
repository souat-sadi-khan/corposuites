<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpeningStockItem;
use App\Models\Product;
use App\Models\ReorderLevel;
use App\Models\StockAdjustmentItem;
use App\Models\StockEntryItem;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LowStockAlertController extends Controller
{
    /**
     * Display the Low Stock Alerts report: every product/warehouse whose
     * current on-hand balance (computed the same way as Inventory
     * Transactions'/Stock Valuation's live balance) has fallen to or below
     * the minimum threshold set in Reorder Level.
     *
     * No new input table — this is a pure read-only report layered on top of
     * data already captured by Opening Stock/Stock Entry/Stock
     * Adjustment/Stock Transfer (the balance) and Reorder Level (the
     * threshold), same "controller only, no CRUD layer" shape as Inventory
     * Transactions/Stock Valuation.
     */
    public function index(Request $request)
    {
        $productId = $request->product_id;
        $warehouseId = $request->warehouse_id;

        $balances = $this->buildCurrentStockBalance($productId, $warehouseId);
        [$specificLevels, $globalLevels] = $this->buildReorderLevels();

        $alerts = $balances->map(function ($row) use ($specificLevels, $globalLevels) {
            $level = $specificLevels->get($row['product_id'] . '|' . $row['warehouse_id'])
                ?? $globalLevels->get($row['product_id']);

            if (!$level) {
                return null;
            }

            $row['reorder_level'] = (float) $level->reorder_level;
            $row['reorder_quantity'] = $level->reorder_quantity !== null ? (float) $level->reorder_quantity : null;
            $row['shortfall'] = $row['reorder_level'] - $row['balance'];

            return $row;
        })->filter()->filter(fn ($row) => $row['balance'] <= $row['reorder_level'])
            ->sortByDesc('shortfall')
            ->values();

        $totalAlerts = $alerts->count();
        $productsAffected = $alerts->pluck('product_id')->unique()->count();
        $warehousesAffected = $alerts->pluck('warehouse_id')->unique()->count();
        $totalShortfallQuantity = $alerts->sum('shortfall');
        $outOfStockCount = $alerts->where('balance', '<=', 0)->count();

        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.low-stock-alerts.index', compact(
            'alerts',
            'totalAlerts',
            'productsAffected',
            'warehousesAffected',
            'totalShortfallQuantity',
            'outOfStockCount',
            'products',
            'warehouses',
            'productId',
            'warehouseId'
        ));
    }

    /**
     * Split active Reorder Level rows into two lookup maps: one keyed by the
     * exact "product_id|warehouse_id" pair (a warehouse-specific threshold),
     * one keyed by just "product_id" (a warehouse_id = null row, applying
     * across all warehouses). A specific row always takes precedence over a
     * global one for the same product/warehouse combination.
     */
    protected function buildReorderLevels(): array
    {
        $levels = ReorderLevel::active()->get();

        $specific = $levels->whereNotNull('warehouse_id')
            ->keyBy(fn ($level) => $level->product_id . '|' . $level->warehouse_id);

        $global = $levels->whereNull('warehouse_id')->keyBy('product_id');

        return [$specific, $global];
    }

    /**
     * Current on-hand balance per product/warehouse, same signed-movement
     * aggregation Inventory Transactions/Stock Valuation use (Opening
     * Stock/Stock Entry in, Stock Adjustment +/-, Stock Transfer out of
     * source/in to destination), excluding cancelled documents. Unlike Stock
     * Valuation, zero/negative balances are deliberately kept (not filtered
     * out) since those are exactly the low-stock/out-of-stock cases this
     * report exists to surface.
     */
    protected function buildCurrentStockBalance(?int $productId, ?int $warehouseId): Collection
    {
        $rows = collect();

        OpeningStockItem::with(['product', 'openingStock.warehouse'])
            ->whereHas('openingStock', fn ($q) => $q->where('entry_status', '!=', 'cancelled'))
            ->get()
            ->each(function ($item) use (&$rows, $productId, $warehouseId) {
                $warehouse = $item->openingStock->warehouse;
                if ($productId && $item->product_id != $productId) return;
                if ($warehouseId && (!$warehouse || $warehouse->id != $warehouseId)) return;

                $rows->push([
                    'product_id' => $item->product_id,
                    'product' => $item->product->name ?? '-',
                    'warehouse_id' => $warehouse->id ?? null,
                    'warehouse' => $warehouse->name ?? '-',
                    'quantity' => (float) $item->quantity,
                ]);
            });

        StockEntryItem::with(['product', 'stockEntry.warehouse'])
            ->whereHas('stockEntry', fn ($q) => $q->where('entry_status', '!=', 'cancelled'))
            ->get()
            ->each(function ($item) use (&$rows, $productId, $warehouseId) {
                $warehouse = $item->stockEntry->warehouse;
                if ($productId && $item->product_id != $productId) return;
                if ($warehouseId && (!$warehouse || $warehouse->id != $warehouseId)) return;

                $rows->push([
                    'product_id' => $item->product_id,
                    'product' => $item->product->name ?? '-',
                    'warehouse_id' => $warehouse->id ?? null,
                    'warehouse' => $warehouse->name ?? '-',
                    'quantity' => (float) $item->quantity,
                ]);
            });

        StockAdjustmentItem::with(['product', 'stockAdjustment.warehouse'])
            ->whereHas('stockAdjustment', fn ($q) => $q->where('adjustment_status', '!=', 'cancelled'))
            ->get()
            ->each(function ($item) use (&$rows, $productId, $warehouseId) {
                $warehouse = $item->stockAdjustment->warehouse;
                if ($productId && $item->product_id != $productId) return;
                if ($warehouseId && (!$warehouse || $warehouse->id != $warehouseId)) return;

                $signedQuantity = $item->adjustment_type === 'decrease' ? -(float) $item->quantity : (float) $item->quantity;

                $rows->push([
                    'product_id' => $item->product_id,
                    'product' => $item->product->name ?? '-',
                    'warehouse_id' => $warehouse->id ?? null,
                    'warehouse' => $warehouse->name ?? '-',
                    'quantity' => $signedQuantity,
                ]);
            });

        StockTransferItem::with(['product', 'stockTransfer.fromWarehouse', 'stockTransfer.toWarehouse'])
            ->whereHas('stockTransfer', fn ($q) => $q->where('transfer_status', '!=', 'cancelled'))
            ->get()
            ->each(function ($item) use (&$rows, $productId, $warehouseId) {
                $fromWarehouse = $item->stockTransfer->fromWarehouse;
                $toWarehouse = $item->stockTransfer->toWarehouse;

                if ($productId && $item->product_id != $productId) return;

                if (!$warehouseId || ($fromWarehouse && $fromWarehouse->id == $warehouseId)) {
                    $rows->push([
                        'product_id' => $item->product_id,
                        'product' => $item->product->name ?? '-',
                        'warehouse_id' => $fromWarehouse->id ?? null,
                        'warehouse' => $fromWarehouse->name ?? '-',
                        'quantity' => -(float) $item->quantity,
                    ]);
                }
                if (!$warehouseId || ($toWarehouse && $toWarehouse->id == $warehouseId)) {
                    $rows->push([
                        'product_id' => $item->product_id,
                        'product' => $item->product->name ?? '-',
                        'warehouse_id' => $toWarehouse->id ?? null,
                        'warehouse' => $toWarehouse->name ?? '-',
                        'quantity' => (float) $item->quantity,
                    ]);
                }
            });

        return $rows->groupBy(fn ($row) => $row['product_id'] . '|' . $row['warehouse_id'])
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'product_id' => $first['product_id'],
                    'product' => $first['product'],
                    'warehouse_id' => $first['warehouse_id'],
                    'warehouse' => $first['warehouse'],
                    'balance' => $group->sum('quantity'),
                ];
            })
            ->values();
    }
}
