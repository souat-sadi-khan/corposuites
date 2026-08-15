<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpeningStock;
use App\Models\OpeningStockItem;
use App\Models\ProductBatch;
use App\Models\ProductSerial;
use App\Models\ReorderLevel;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockCount;
use App\Models\StockEntry;
use App\Models\StockEntryItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InventoryReportController extends Controller
{
    /**
     * Display the Inventory reporting dashboard — a single-page snapshot
     * across every Inventory module built so far (Warehouses, the five
     * movement documents, Batch/Serial Management, Reorder Level/Low Stock
     * Alerts, and the Stock Valuation figure), same "controller only, no new
     * table/Model/Service" pattern as every other *-report.view page in this
     * project. All balance/valuation/alert math is a self-contained copy of
     * the logic already established in InventoryTransactionController /
     * StockValuationController / LowStockAlertController, per this project's
     * standing precedent that each report computes its own aggregations
     * independently rather than sharing a service.
     */
    public function index(Request $request)
    {
        $totalWarehouses = Warehouse::count();
        $activeWarehouses = Warehouse::active()->count();

        $documentCounts = [
            'Stock Entry' => StockEntry::count(),
            'Opening Stock' => OpeningStock::count(),
            'Stock Adjustment' => StockAdjustment::count(),
            'Stock Transfer' => StockTransfer::count(),
            'Stock Count' => StockCount::count(),
        ];
        $totalMovementDocuments = array_sum($documentCounts);

        $totalBatches = ProductBatch::count();
        $expiredBatches = ProductBatch::expired()->count();
        $totalSerials = ProductSerial::count();
        $serialsByStatus = ProductSerial::query()
            ->get()
            ->groupBy(fn ($serial) => ucfirst(str_replace('_', ' ', $serial->serial_status)))
            ->map->count();

        $balances = $this->buildCurrentStockBalance();
        $totalProductsTracked = $balances->pluck('product_id')->unique()->count();

        $averageCosts = $this->buildWeightedAverageCosts();
        $totalInventoryValue = $balances
            ->filter(fn ($row) => $row['balance'] > 0)
            ->sum(function ($row) use ($averageCosts) {
                $avgCost = $averageCosts->get($row['product_id']);
                return $avgCost !== null ? round($row['balance'] * $avgCost, 2) : 0;
            });

        $lowStockAlerts = $this->buildLowStockAlerts($balances);
        $totalLowStockAlerts = $lowStockAlerts->count();
        $outOfStockCount = $lowStockAlerts->where('balance', '<=', 0)->count();

        $topLowStockAlerts = $lowStockAlerts->take(10);

        return view('admin.inventory-reports.index', compact(
            'totalWarehouses',
            'activeWarehouses',
            'documentCounts',
            'totalMovementDocuments',
            'totalBatches',
            'expiredBatches',
            'totalSerials',
            'serialsByStatus',
            'totalProductsTracked',
            'totalInventoryValue',
            'totalLowStockAlerts',
            'outOfStockCount',
            'topLowStockAlerts'
        ));
    }

    /**
     * Every product/warehouse whose balance has fallen to or below its
     * configured Reorder Level, sorted by largest shortfall first — a
     * compact re-derivation of LowStockAlertController's own logic, scoped
     * here to no product/warehouse filter since this is a whole-inventory
     * snapshot, not a filterable drill-down screen.
     */
    protected function buildLowStockAlerts(Collection $balances): Collection
    {
        $levels = ReorderLevel::active()->get();
        $specific = $levels->whereNotNull('warehouse_id')->keyBy(fn ($level) => $level->product_id . '|' . $level->warehouse_id);
        $global = $levels->whereNull('warehouse_id')->keyBy('product_id');

        return $balances->map(function ($row) use ($specific, $global) {
            $level = $specific->get($row['product_id'] . '|' . $row['warehouse_id']) ?? $global->get($row['product_id']);

            if (!$level) {
                return null;
            }

            $row['reorder_level'] = (float) $level->reorder_level;
            $row['shortfall'] = $row['reorder_level'] - $row['balance'];

            return $row;
        })->filter()->filter(fn ($row) => $row['balance'] <= $row['reorder_level'])
            ->sortByDesc('shortfall')
            ->values();
    }

    /**
     * Current on-hand balance per product/warehouse, same signed-movement
     * aggregation Inventory Transactions/Stock Valuation/Low Stock Alerts
     * use, excluding cancelled documents.
     */
    protected function buildCurrentStockBalance(): Collection
    {
        $rows = collect();

        OpeningStockItem::with(['product', 'openingStock.warehouse'])
            ->whereHas('openingStock', fn ($q) => $q->where('entry_status', '!=', 'cancelled'))
            ->get()
            ->each(function ($item) use (&$rows) {
                $warehouse = $item->openingStock->warehouse;
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
            ->each(function ($item) use (&$rows) {
                $warehouse = $item->stockEntry->warehouse;
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
            ->each(function ($item) use (&$rows) {
                $warehouse = $item->stockAdjustment->warehouse;
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
            ->each(function ($item) use (&$rows) {
                $fromWarehouse = $item->stockTransfer->fromWarehouse;
                $toWarehouse = $item->stockTransfer->toWarehouse;

                $rows->push([
                    'product_id' => $item->product_id,
                    'product' => $item->product->name ?? '-',
                    'warehouse_id' => $fromWarehouse->id ?? null,
                    'warehouse' => $fromWarehouse->name ?? '-',
                    'quantity' => -(float) $item->quantity,
                ]);
                $rows->push([
                    'product_id' => $item->product_id,
                    'product' => $item->product->name ?? '-',
                    'warehouse_id' => $toWarehouse->id ?? null,
                    'warehouse' => $toWarehouse->name ?? '-',
                    'quantity' => (float) $item->quantity,
                ]);
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

    /**
     * Weighted-average unit cost per product, same derivation as Stock
     * Valuation (Opening Stock + Stock Entry unit_cost, nullable lines
     * excluded from the average rather than treated as zero).
     */
    protected function buildWeightedAverageCosts(): Collection
    {
        $costLines = collect();

        OpeningStockItem::whereNotNull('unit_cost')
            ->whereHas('openingStock', fn ($q) => $q->where('entry_status', '!=', 'cancelled'))
            ->get(['product_id', 'quantity', 'unit_cost'])
            ->each(fn ($item) => $costLines->push($item));

        StockEntryItem::whereNotNull('unit_cost')
            ->whereHas('stockEntry', fn ($q) => $q->where('entry_status', '!=', 'cancelled'))
            ->get(['product_id', 'quantity', 'unit_cost'])
            ->each(fn ($item) => $costLines->push($item));

        return $costLines->groupBy('product_id')->map(function ($lines) {
            $totalQuantity = $lines->sum('quantity');
            $totalCost = $lines->sum(fn ($line) => $line->quantity * $line->unit_cost);

            return $totalQuantity > 0 ? round($totalCost / $totalQuantity, 2) : null;
        })->filter(fn ($cost) => $cost !== null);
    }
}
