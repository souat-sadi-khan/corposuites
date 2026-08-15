<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpeningStockItem;
use App\Models\Product;
use App\Models\StockAdjustmentItem;
use App\Models\StockEntryItem;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StockValuationController extends Controller
{
    /**
     * Display the Stock Valuation report: current on-hand quantity per
     * product/warehouse (computed the same way as Inventory Transactions'
     * live balance) multiplied by a weighted-average unit cost derived from
     * every cost-bearing inbound movement recorded for that product.
     *
     * No stored "average cost" column exists anywhere in this project — the
     * cost is derived fresh from Opening Stock/Stock Entry/Stock Adjustment
     * unit_cost figures every time this report is viewed, same "compute, never
     * persist a driftable figure" philosophy as Inventory Transactions' balance.
     */
    public function index(Request $request)
    {
        $productId = $request->product_id;
        $warehouseId = $request->warehouse_id;

        $balances = $this->buildCurrentStockBalance($productId, $warehouseId);
        $averageCosts = $this->buildWeightedAverageCosts();

        $valuation = $balances->map(function ($row) use ($averageCosts) {
            $avgCost = $averageCosts->get($row['product_id']);
            $row['avg_unit_cost'] = $avgCost;
            $row['total_value'] = $avgCost !== null ? round($row['balance'] * $avgCost, 2) : null;

            return $row;
        });

        $totalInventoryValue = $valuation->sum('total_value');
        $totalQuantityOnHand = $valuation->sum('balance');
        $productsWithNoCostData = $valuation->whereNull('avg_unit_cost')->count();
        $totalLinesValued = $valuation->whereNotNull('total_value')->count();

        $valuation = $valuation->sortByDesc('total_value')->values();

        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.stock-valuation.index', compact(
            'valuation',
            'totalInventoryValue',
            'totalQuantityOnHand',
            'productsWithNoCostData',
            'totalLinesValued',
            'products',
            'warehouses',
            'productId',
            'warehouseId'
        ));
    }

    /**
     * Current on-hand balance per product/warehouse, same signed-movement
     * aggregation Inventory Transactions uses (Opening Stock/Stock Entry in,
     * Stock Adjustment +/-, Stock Transfer out of source/in to destination),
     * excluding cancelled documents.
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
            ->filter(fn ($row) => $row['balance'] > 0)
            ->values();
    }

    /**
     * Weighted-average unit cost per product, derived from every cost-bearing
     * inbound movement (Opening Stock + Stock Entry unit_cost, both nullable —
     * lines with no recorded cost are simply excluded from the average, not
     * treated as zero) across all warehouses combined.
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
