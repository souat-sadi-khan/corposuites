<?php

namespace App\Services;

use App\Models\SalesQuotation;

class SalesQuotationService
{
    public function create(array $data): SalesQuotation
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['quotation_number'] = $this->generateQuotationNumber();
        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $salesQuotation = SalesQuotation::create($data);
        $this->syncItems($salesQuotation, $items);

        return $salesQuotation;
    }

    public function update(SalesQuotation $salesQuotation, array $data): SalesQuotation
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $salesQuotation->update($data);
        $this->syncItems($salesQuotation, $items);

        return $salesQuotation;
    }

    public function delete(SalesQuotation $salesQuotation): bool
    {
        return $salesQuotation->delete();
    }

    protected function syncItems(SalesQuotation $salesQuotation, array $items): void
    {
        $salesQuotation->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount;

            $salesQuotation->items()->create([
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

    protected function generateQuotationNumber(): string
    {
        $lastId = SalesQuotation::max('id') ?? 0;
        return 'SQ-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
