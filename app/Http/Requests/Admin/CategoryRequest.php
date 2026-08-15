<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('category') ? $this->route('category')->id : null;

        $parentIdRules = ['nullable', 'exists:categories,id'];
        if ($id) {
            $parentIdRules[] = Rule::notIn([$id]);
        }

        return [
            'parent_id' => $parentIdRules,
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where(fn($q) => $q->where('parent_id', $this->parent_id))->ignore($id),
            ],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $category = $this->route('category');

            if (!$category || !$this->parent_id) {
                return;
            }

            if (in_array((int) $this->parent_id, $category->descendantIds(), true)) {
                $validator->errors()->add('parent_id', 'A category cannot be moved under one of its own subcategories.');
            }
        });
    }
}
