<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\SupplierQuotation;

class PurchaseOrderService
{
    public function create(array $data): PurchaseOrder
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['po_number'] = $this->generatePoNumber();
        $data = array_merge($data, $this->calculateTotals($items));

        $purchaseOrder = PurchaseOrder::create($data);
        $this->syncItems($purchaseOrder, $items);
        $this->markSupplierQuotationSelected($purchaseOrder);

        return $purchaseOrder;
    }

    public function update(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data = array_merge($data, $this->calculateTotals($items));

        $purchaseOrder->update($data);
        $this->syncItems($purchaseOrder, $items);
        $this->markSupplierQuotationSelected($purchaseOrder);

        return $purchaseOrder;
    }

    public function delete(PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->delete();
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

    protected function syncItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        $purchaseOrder->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount;

            $purchaseOrder->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
            ]);
        }
    }

    /**
     * When a PO is generated against a source supplier quotation, mark that
     * quotation as the one selected among the vendors that were quoted.
     */
    protected function markSupplierQuotationSelected(PurchaseOrder $purchaseOrder): void
    {
        if (!$purchaseOrder->supplier_quotation_id) {
            return;
        }

        SupplierQuotation::where('id', $purchaseOrder->supplier_quotation_id)
            ->update(['quotation_status' => 'selected']);
    }

    protected function generatePoNumber(): string
    {
        $lastId = PurchaseOrder::max('id') ?? 0;

        return 'PO-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
