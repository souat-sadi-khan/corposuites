<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductVariantRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('product_variant') ? $this->route('product_variant')->id : null;

        return [
            'product_id' => ['required', 'exists:products,id'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($id)],
            'attribute_value_ids' => ['required', 'array', 'min:1'],
            'attribute_value_ids.*' => ['exists:attribute_values,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if (!$this->product_id || !is_array($this->attribute_value_ids)) {
                return;
            }

            $currentVariant = $this->route('product_variant');
            $submittedIds = collect($this->attribute_value_ids)->map(fn($id) => (int) $id)->sort()->values();

            $duplicate = ProductVariant::where('product_id', $this->product_id)
                ->when($currentVariant, fn($q) => $q->where('id', '!=', $currentVariant->id))
                ->with('attributeValues')
                ->get()
                ->first(function ($variant) use ($submittedIds) {
                    return $variant->attributeValues->pluck('id')->sort()->values()->all() === $submittedIds->all();
                });

            if ($duplicate) {
                $validator->errors()->add('attribute_value_ids', 'A variant with this exact combination of attribute values already exists for this product.');
            }
        });
    }
}
