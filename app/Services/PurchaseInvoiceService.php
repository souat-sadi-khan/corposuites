<?php

namespace App\Services;

use App\Models\GoodsReceiptItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrderItem;

class PurchaseInvoiceService
{
    public function create(array $data): PurchaseInvoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['invoice_number'] = $this->generateInvoiceNumber();
        $data = array_merge($data, $this->calculateTotals($items));
        $data['match_status'] = $this->calculateMatchStatus($data['purchase_order_id'] ?? null, $data['goods_receipt_id'] ?? null, $items);

        $purchaseInvoice = PurchaseInvoice::create($data);
        $this->syncItems($purchaseInvoice, $items);

        return $purchaseInvoice;
    }

    public function update(PurchaseInvoice $purchaseInvoice, array $data): PurchaseInvoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data = array_merge($data, $this->calculateTotals($items));
        $data['match_status'] = $this->calculateMatchStatus($data['purchase_order_id'] ?? null, $data['goods_receipt_id'] ?? null, $items);

        $purchaseInvoice->update($data);
        $this->syncItems($purchaseInvoice, $items);

        return $purchaseInvoice;
    }

    public function delete(PurchaseInvoice $purchaseInvoice): bool
    {
        return $purchaseInvoice->delete();
    }

    protected function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);

            $subtotal += $quantity * $unitPrice;
            $discountTotal += $discount;
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'grand_total' => $subtotal - $discountTotal,
        ];
    }

    protected function syncItems(PurchaseInvoice $purchaseInvoice, array $items): void
    {
        $purchaseInvoice->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount;

            $purchaseInvoice->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
            ]);
        }
    }

    /**
     * Three-way match: invoiced quantity is checked against what was actually
     * received (Goods Receipt) when one is linked, falling back to the ordered
     * quantity (Purchase Order) otherwise; invoiced unit price is always checked
     * against the agreed Purchase Order price when a PO is linked. With neither
     * source linked there is nothing to match against.
     */
    protected function calculateMatchStatus(?int $purchaseOrderId, ?int $goodsReceiptId, array $items): string
    {
        if (!$purchaseOrderId && !$goodsReceiptId) {
            return 'unmatched';
        }

        $poQuantities = $poPrices = $grQuantities = collect();

        if ($purchaseOrderId) {
            $poItems = PurchaseOrderItem::where('purchase_order_id', $purchaseOrderId)->get();
            $poQuantities = $poItems->keyBy('product_id')->map(fn ($i) => (float) $i->quantity);
            $poPrices = $poItems->keyBy('product_id')->map(fn ($i) => (float) $i->unit_price);
        }

        if ($goodsReceiptId) {
            $grQuantities = GoodsReceiptItem::where('goods_receipt_id', $goodsReceiptId)
                ->get()
                ->groupBy('product_id')
                ->map(fn ($rows) => (float) $rows->sum('quantity_received'));
        }

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            $expectedQuantity = $goodsReceiptId ? $grQuantities->get($productId) : $poQuantities->get($productId);

            if ($expectedQuantity === null || round($expectedQuantity, 2) !== round($quantity, 2)) {
                return 'discrepancy';
            }

            if ($purchaseOrderId) {
                $expectedPrice = $poPrices->get($productId);

                if ($expectedPrice === null || round($expectedPrice, 2) !== round($unitPrice, 2)) {
                    return 'discrepancy';
                }
            }
        }

        return 'matched';
    }

    protected function generateInvoiceNumber(): string
    {
        $lastId = PurchaseInvoice::max('id') ?? 0;

        return 'PINV-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
