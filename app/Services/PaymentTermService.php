<?php

namespace App\Services;

use App\Models\PaymentTerm;

class PaymentTermService
{
    public function create(array $data): PaymentTerm
    {
        return PaymentTerm::create($data);
    }

    public function update(PaymentTerm $paymentTerm, array $data): PaymentTerm
    {
        $paymentTerm->update($data);
        return $paymentTerm;
    }

    public function delete(PaymentTerm $paymentTerm): bool
    {
        return $paymentTerm->delete();
    }
}
