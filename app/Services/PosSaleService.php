<?php

namespace App\Services;

use App\Models\PosSale;

class PosSaleService
{
    public function checkout(array $data): PosSale
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['pos_number'] = $this->generatePosNumber();
        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $amountTendered = isset($data['amount_tendered']) ? (float) $data['amount_tendered'] : null;
        $data['change_due'] = $amountTendered !== null ? max(0, $amountTendered - $totals['grand_total']) : 0;

        $posSale = PosSale::create($data);
        $this->syncItems($posSale, $items);

        return $posSale;
    }

    public function void(PosSale $posSale): PosSale
    {
        $posSale->update(['pos_status' => 'voided']);

        return $posSale;
    }

    public function delete(PosSale $posSale): bool
    {
        return $posSale->delete();
    }

    protected function syncItems(PosSale $posSale, array $items): void
    {
        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount;

            $posSale->items()->create([
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

    protected function generatePosNumber(): string
    {
        $lastId = PosSale::max('id') ?? 0;
        return 'POS-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
