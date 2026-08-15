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

class InventoryTransactionController extends Controller
{
    /**
     * Display the unified inventory transaction ledger: a merged, chronological
     * log of every stock movement recorded across Opening Stock, Stock Entry,
     * Stock Adjustment, and Stock Transfer, plus the resulting current stock
     * balance per product/warehouse computed from that same log.
     *
     * This module deliberately introduces no new input table — every prior
     * Inventory module already captures the actual movement; this is the
     * read-only ledger that reads across all of them, exactly as flagged in
     * each of their own changelog entries ("deferred to Inventory Transactions").
     */
    public function index(Request $request)
    {
        $productId = $request->product_id;
        $warehouseId = $request->warehouse_id;

        $transactions = $this->buildTransactionLog($productId, $warehouseId);

        $totalIn = $transactions->where('quantity', '>', 0)->sum('quantity');
        $totalOut = abs($transactions->where('quantity', '<', 0)->sum('quantity'));
        $netMovement = $totalIn - $totalOut;

        $currentStock = $this->buildCurrentStockBalance($productId, $warehouseId);
        $totalProductsTracked = $currentStock->pluck('product_id')->unique()->count();
        $totalWarehousesTracked = $currentStock->pluck('warehouse_id')->unique()->count();
        $zeroOrNegativeCount = $currentStock->where('balance', '<=', 0)->count();

        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.inventory-transactions.index', compact(
            'transactions',
            'currentStock',
            'totalIn',
            'totalOut',
            'netMovement',
            'totalProductsTracked',
            'totalWarehousesTracked',
            'zeroOrNegativeCount',
            'products',
            'warehouses',
            'productId',
            'warehouseId'
        ));
    }

    /**
     * Merge every source table's line items into one chronological, signed
     * quantity log (positive = stock in, negative = stock out).
     */
    protected function buildTransactionLog(?int $productId, ?int $warehouseId): Collection
    {
        $entries = collect();

        OpeningStockItem::with(['product', 'openingStock.warehouse'])
            ->whereHas('openingStock', fn ($q) => $q->where('entry_status', '!=', 'cancelled'))
            ->get()
            ->each(function ($item) use (&$entries, $productId, $warehouseId) {
                $warehouse = $item->openingStock->warehouse;
                if ($productId && $item->product_id != $productId) return;
                if ($warehouseId && (!$warehouse || $warehouse->id != $warehouseId)) return;

                $entries->push([
                    'date' => $item->openingStock->opening_date,
                    'type' => 'Opening Stock',
                    'reference' => $item->openingStock->entry_number,
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
            ->each(function ($item) use (&$entries, $productId, $warehouseId) {
                $warehouse = $item->stockEntry->warehouse;
                if ($productId && $item->product_id != $productId) return;
                if ($warehouseId && (!$warehouse || $warehouse->id != $warehouseId)) return;

                $entries->push([
                    'date' => $item->stockEntry->entry_date,
                    'type' => 'Stock Entry',
                    'reference' => $item->stockEntry->entry_number,
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
            ->each(function ($item) use (&$entries, $productId, $warehouseId) {
                $warehouse = $item->stockAdjustment->warehouse;
                if ($productId && $item->product_id != $productId) return;
                if ($warehouseId && (!$warehouse || $warehouse->id != $warehouseId)) return;

                $signedQuantity = $item->adjustment_type === 'decrease' ? -(float) $item->quantity : (float) $item->quantity;

                $entries->push([
                    'date' => $item->stockAdjustment->adjustment_date,
                    'type' => 'Stock Adjustment (' . ucfirst($item->adjustment_type) . ')',
                    'reference' => $item->stockAdjustment->adjustment_number,
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
            ->each(function ($item) use (&$entries, $productId, $warehouseId) {
                $fromWarehouse = $item->stockTransfer->fromWarehouse;
                $toWarehouse = $item->stockTransfer->toWarehouse;

                if (!$productId || $item->product_id == $productId) {
                    if (!$warehouseId || ($fromWarehouse && $fromWarehouse->id == $warehouseId)) {
                        $entries->push([
                            'date' => $item->stockTransfer->transfer_date,
                            'type' => 'Stock Transfer (Out)',
                            'reference' => $item->stockTransfer->transfer_number,
                            'product_id' => $item->product_id,
                            'product' => $item->product->name ?? '-',
                            'warehouse_id' => $fromWarehouse->id ?? null,
                            'warehouse' => $fromWarehouse->name ?? '-',
                            'quantity' => -(float) $item->quantity,
                        ]);
                    }
                    if (!$warehouseId || ($toWarehouse && $toWarehouse->id == $warehouseId)) {
                        $entries->push([
                            'date' => $item->stockTransfer->transfer_date,
                            'type' => 'Stock Transfer (In)',
                            'reference' => $item->stockTransfer->transfer_number,
                            'product_id' => $item->product_id,
                            'product' => $item->product->name ?? '-',
                            'warehouse_id' => $toWarehouse->id ?? null,
                            'warehouse' => $toWarehouse->name ?? '-',
                            'quantity' => (float) $item->quantity,
                        ]);
                    }
                }
            });

        return $entries->sortByDesc('date')->values();
    }

    /**
     * Compute the current stock balance per product/warehouse by summing every
     * signed movement in the transaction log. This is the "live balance" every
     * prior Inventory module's changelog deferred to this module.
     */
    protected function buildCurrentStockBalance(?int $productId, ?int $warehouseId): Collection
    {
        return $this->buildTransactionLog($productId, $warehouseId)
            ->groupBy(fn ($row) => $row['product_id'] . '|' . $row['warehouse_id'])
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'product_id' => $first['product_id'],
                    'product' => $first['product'],
                    'warehouse_id' => $first['warehouse_id'],
                    'warehouse' => $first['warehouse'],
                    'balance' => $rows->sum('quantity'),
                ];
            })
            ->sortBy('product')
            ->values();
    }
}
