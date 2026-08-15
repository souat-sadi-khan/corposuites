<?php

namespace App\Services;

use App\Models\CreditNote;

class CreditNoteService
{
    public function create(array $data): CreditNote
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['credit_note_number'] = $this->generateCreditNoteNumber();
        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $creditNote = CreditNote::create($data);
        $this->syncItems($creditNote, $items);

        return $creditNote;
    }

    public function update(CreditNote $creditNote, array $data): CreditNote
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $creditNote->update($data);
        $this->syncItems($creditNote, $items);

        return $creditNote;
    }

    public function delete(CreditNote $creditNote): bool
    {
        return $creditNote->delete();
    }

    protected function syncItems(CreditNote $creditNote, array $items): void
    {
        $creditNote->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount;

            $creditNote->items()->create([
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

    protected function generateCreditNoteNumber(): string
    {
        $lastId = CreditNote::max('id') ?? 0;
        return 'CN-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
