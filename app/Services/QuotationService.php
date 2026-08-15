<?php

namespace App\Services;

use App\Models\Quotation;

class QuotationService
{
    public function create(array $data): Quotation
    {
        $data['quotation_number'] = $this->generateQuotationNumber();

        return Quotation::create($data);
    }

    public function update(Quotation $quotation, array $data): Quotation
    {
        $quotation->update($data);
        return $quotation;
    }

    public function delete(Quotation $quotation): bool
    {
        return $quotation->delete();
    }

    protected function generateQuotationNumber(): string
    {
        $lastId = Quotation::max('id') ?? 0;

        return 'QUO-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
