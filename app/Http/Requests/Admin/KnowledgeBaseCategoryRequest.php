<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowledgeBaseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('knowledge_base_category')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('knowledge_base_categories', 'name')->ignore($id)],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A knowledge base category with this name already exists.',
        ];
    }
}
