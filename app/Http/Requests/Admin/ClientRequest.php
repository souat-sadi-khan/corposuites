<?php

namespace App\Http\Requests\Admin;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('client')?->id;

        return [
            // client_code is deliberately absent — server-generated only,
            // exactly like Vendor.vendor_code / Customer.customer_code.
            'name' => 'required|string|max:255',
            'client_type' => ['required', Rule::in(Client::TYPES)],
            // A company client should say which company it is; an individual
            // client legitimately has none.
            'company_name' => 'nullable|string|max:255|required_if:client_type,company',
            'contact_person' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($id),
            ],
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required_if' => 'Company name is required for a company client.',
            'email.unique' => 'Another client is already registered with this email address.',
        ];
    }
}
