<?php

namespace App\Services;

use App\Models\DeliveryNote;

class DeliveryNoteService
{
    public function create(array $data): DeliveryNote
    {
        $data['note_number'] = $this->generateNoteNumber();

        return DeliveryNote::create($data);
    }

    public function update(DeliveryNote $deliveryNote, array $data): DeliveryNote
    {
        $deliveryNote->update($data);

        return $deliveryNote;
    }

    public function delete(DeliveryNote $deliveryNote): bool
    {
        return $deliveryNote->delete();
    }

    protected function generateNoteNumber(): string
    {
        $lastId = DeliveryNote::max('id') ?? 0;
        return 'DN-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
