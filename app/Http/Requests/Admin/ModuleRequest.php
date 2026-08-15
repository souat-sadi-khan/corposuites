<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModuleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('module') ? $this->route('module')->id : null;
        return [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('modules', 'slug')->ignore($id)],
            'version' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'status' => 'required|boolean',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'slug' => $this->slug ?? \Illuminate\Support\Str::slug($this->name),
        ]);
    }
}
