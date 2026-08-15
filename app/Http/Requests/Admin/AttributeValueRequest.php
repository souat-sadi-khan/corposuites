<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttributeValueRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('attribute_value') ? $this->route('attribute_value')->id : null;

        return [
            'product_attribute_id' => ['required', 'exists:product_attributes,id'],
            'value' => [
                'required', 'string', 'max:255',
                Rule::unique('attribute_values', 'value')->where(fn($q) => $q->where('product_attribute_id', $this->product_attribute_id))->ignore($id),
            ],
            'status' => 'required|boolean',
        ];
    }
}
