<?php

namespace App\Services;

use App\Models\DebitNote;

class DebitNoteService
{
    public function create(array $data): DebitNote
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['debit_note_number'] = $this->generateDebitNoteNumber();
        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $debitNote = DebitNote::create($data);
        $this->syncItems($debitNote, $items);

        return $debitNote;
    }

    public function update(DebitNote $debitNote, array $data): DebitNote
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->calculateTotals($items);
        $data = array_merge($data, $totals);

        $debitNote->update($data);
        $this->syncItems($debitNote, $items);

        return $debitNote;
    }

    public function delete(DebitNote $debitNote): bool
    {
        return $debitNote->delete();
    }

    protected function syncItems(DebitNote $debitNote, array $items): void
    {
        $debitNote->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount;

            $debitNote->items()->create([
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

    protected function generateDebitNoteNumber(): string
    {
        $lastId = DebitNote::max('id') ?? 0;

        return 'DBN-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
