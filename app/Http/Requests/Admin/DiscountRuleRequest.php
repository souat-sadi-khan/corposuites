<?php

namespace App\Http\Requests\Admin;

use App\Models\DiscountRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscountRuleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $valueRules = ['required', 'numeric', 'min:0'];
        if ($this->discount_type === 'percentage') {
            $valueRules[] = 'max:100';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in(DiscountRule::DISCOUNT_TYPES)],
            'value' => $valueRules,
            'scope_type' => ['required', Rule::in(DiscountRule::SCOPE_TYPES)],
            'category_id' => ['nullable', 'required_if:scope_type,category', 'exists:categories,id'],
            'product_id' => ['nullable', 'required_if:scope_type,product', 'exists:products,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'category_id.required_if' => 'Please select a category for a category-scoped rule.',
            'product_id.required_if' => 'Please select a product for a product-scoped rule.',
            'value.max' => 'A percentage discount cannot exceed 100.',
        ];
    }
}
