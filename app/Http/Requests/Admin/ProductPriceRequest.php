<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductPriceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('product_price') ? $this->route('product_price')->id : null;

        return [
            'product_id' => ['required', 'exists:products,id'],
            'price_tier_id' => [
                'required', 'exists:price_tiers,id',
                Rule::unique('product_prices', 'price_tier_id')->where(fn($q) => $q->where('product_id', $this->product_id))->ignore($id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages()
    {
        return [
            'price_tier_id.unique' => 'This product already has a price set for this tier.',
        ];
    }
}
