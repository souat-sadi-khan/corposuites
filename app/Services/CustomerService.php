<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function create(array $data): Customer
    {
        $data['customer_code'] = $this->generateCustomerCode();

        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer;
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    protected function generateCustomerCode(): string
    {
        $lastId = Customer::max('id') ?? 0;

        return 'CUST-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
