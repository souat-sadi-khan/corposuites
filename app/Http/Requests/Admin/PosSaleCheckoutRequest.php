<?php

namespace App\Http\Requests\Admin;

use App\Models\PosSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PosSaleCheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_method' => ['required', Rule::in(PosSale::PAYMENT_METHODS)],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages()
    {
        return [
            'items.*.product_id.distinct' => 'The same product cannot be added twice to a sale.',
            'items.required' => 'Add at least one product to the cart before checking out.',
        ];
    }
}
