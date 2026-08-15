<?php

namespace App\Services;

use App\Models\SalesOrder;

class SalesOrderService
{
    public function create(array $data): SalesOrder
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['order_number'] = $this->generateOrderNumber();
        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $salesOrder = SalesOrder::create($data);
        $this->syncItems($salesOrder, $items);

        return $salesOrder;
    }

    public function update(SalesOrder $salesOrder, array $data): SalesOrder
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $salesOrder->update($data);
        $this->syncItems($salesOrder, $items);

        return $salesOrder;
    }

    public function delete(SalesOrder $salesOrder): bool
    {
        return $salesOrder->delete();
    }

    protected function syncItems(SalesOrder $salesOrder, array $items): void
    {
        $salesOrder->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount;

            $salesOrder->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
            ]);
        }
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

    protected function generateOrderNumber(): string
    {
        $lastId = SalesOrder::max('id') ?? 0;
        return 'SO-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
