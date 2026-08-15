<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductImageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'product_id' => 'required|exists:products,id',
            'image' => ($isUpdate ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_primary' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
        ];
    }
}
