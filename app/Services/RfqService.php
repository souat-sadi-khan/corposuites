<?php

namespace App\Services;

use App\Models\Rfq;

class RfqService
{
    public function create(array $data): Rfq
    {
        $items = $data['items'] ?? [];
        $vendorIds = $data['vendor_ids'] ?? [];
        unset($data['items'], $data['vendor_ids']);

        $data['rfq_number'] = $this->generateRfqNumber();

        $rfq = Rfq::create($data);
        $this->syncItems($rfq, $items);
        $this->syncVendors($rfq, $vendorIds);

        return $rfq;
    }

    public function update(Rfq $rfq, array $data): Rfq
    {
        $items = $data['items'] ?? [];
        $vendorIds = $data['vendor_ids'] ?? [];
        unset($data['items'], $data['vendor_ids']);

        $rfq->update($data);
        $this->syncItems($rfq, $items);
        $this->syncVendors($rfq, $vendorIds);

        return $rfq;
    }

    public function delete(Rfq $rfq): bool
    {
        return $rfq->delete();
    }

    protected function syncItems(Rfq $rfq, array $items): void
    {
        $rfq->items()->delete();

        foreach ($items as $item) {
            $rfq->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function syncVendors(Rfq $rfq, array $vendorIds): void
    {
        $existing = $rfq->rfqVendors()->pluck('sent_status', 'vendor_id');

        $rfq->rfqVendors()->delete();

        foreach ($vendorIds as $vendorId) {
            $rfq->rfqVendors()->create([
                'vendor_id' => $vendorId,
                'sent_status' => $existing->get($vendorId, 'pending'),
            ]);
        }
    }

    protected function generateRfqNumber(): string
    {
        $lastId = Rfq::max('id') ?? 0;

        return 'RFQ-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
