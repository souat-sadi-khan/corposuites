<?php

namespace App\Services;

use App\Models\SalesInvoice;

class SalesInvoiceService
{
    public function create(array $data): SalesInvoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['invoice_number'] = $this->generateInvoiceNumber();
        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $salesInvoice = SalesInvoice::create($data);
        $this->syncItems($salesInvoice, $items);

        return $salesInvoice;
    }

    public function update(SalesInvoice $salesInvoice, array $data): SalesInvoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $salesInvoice->update($data);
        $this->syncItems($salesInvoice, $items);

        return $salesInvoice;
    }

    public function delete(SalesInvoice $salesInvoice): bool
    {
        return $salesInvoice->delete();
    }

    protected function syncItems(SalesInvoice $salesInvoice, array $items): void
    {
        $salesInvoice->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount;

            $salesInvoice->items()->create([
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

    protected function generateInvoiceNumber(): string
    {
        $lastId = SalesInvoice::max('id') ?? 0;
        return 'INV-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
