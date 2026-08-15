<?php

namespace App\Services;

use App\Models\RfqVendor;
use App\Models\SupplierQuotation;

class SupplierQuotationService
{
    public function create(array $data): SupplierQuotation
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['quotation_number'] = $this->generateQuotationNumber();
        $data = array_merge($data, $this->calculateTotals($items));

        $supplierQuotation = SupplierQuotation::create($data);
        $this->syncItems($supplierQuotation, $items);
        $this->markRfqVendorResponded($supplierQuotation);

        return $supplierQuotation;
    }

    public function update(SupplierQuotation $supplierQuotation, array $data): SupplierQuotation
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data = array_merge($data, $this->calculateTotals($items));

        $supplierQuotation->update($data);
        $this->syncItems($supplierQuotation, $items);
        $this->markRfqVendorResponded($supplierQuotation);

        return $supplierQuotation;
    }

    public function delete(SupplierQuotation $supplierQuotation): bool
    {
        return $supplierQuotation->delete();
    }

    protected function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;

        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
            $discountTotal += $item['discount'] ?? 0;
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'grand_total' => $subtotal - $discountTotal,
        ];
    }

    protected function syncItems(SupplierQuotation $supplierQuotation, array $items): void
    {
        $supplierQuotation->items()->delete();

        foreach ($items as $item) {
            $lineTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);

            $supplierQuotation->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'line_total' => $lineTotal,
            ]);
        }
    }

    /**
     * When a quotation is captured against an RFQ+vendor pair, flip that vendor's
     * rfq_vendors.sent_status to 'responded' so RFQ Management reflects it.
     */
    protected function markRfqVendorResponded(SupplierQuotation $supplierQuotation): void
    {
        if (!$supplierQuotation->rfq_id) {
            return;
        }

        RfqVendor::where('rfq_id', $supplierQuotation->rfq_id)
            ->where('vendor_id', $supplierQuotation->vendor_id)
            ->update(['sent_status' => 'responded']);
    }

    protected function generateQuotationNumber(): string
    {
        $lastId = SupplierQuotation::max('id') ?? 0;

        return 'SUPQ-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
