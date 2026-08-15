<?php

namespace App\Services;

use App\Models\PurchaseRequest;

class PurchaseRequestService
{
    public function create(array $data): PurchaseRequest
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['request_number'] = $this->generateRequestNumber();

        $purchaseRequest = PurchaseRequest::create($data);
        $this->syncItems($purchaseRequest, $items);

        return $purchaseRequest;
    }

    public function update(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $purchaseRequest->update($data);
        $this->syncItems($purchaseRequest, $items);

        return $purchaseRequest;
    }

    public function delete(PurchaseRequest $purchaseRequest): bool
    {
        return $purchaseRequest->delete();
    }

    public function approve(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        $purchaseRequest->update(['request_status' => 'approved']);

        return $purchaseRequest;
    }

    public function reject(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        $purchaseRequest->update(['request_status' => 'rejected']);

        return $purchaseRequest;
    }

    protected function syncItems(PurchaseRequest $purchaseRequest, array $items): void
    {
        $purchaseRequest->items()->delete();

        foreach ($items as $item) {
            $purchaseRequest->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateRequestNumber(): string
    {
        $lastId = PurchaseRequest::max('id') ?? 0;

        return 'PR-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
