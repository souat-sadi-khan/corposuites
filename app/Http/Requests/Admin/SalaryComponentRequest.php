<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryComponentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('salary_component') ? $this->route('salary_component')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('salary_components', 'name')->ignore($id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('salary_components', 'code')->ignore($id)],
            'type' => 'required|in:earning,deduction',
            'calculation_type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'is_taxable' => 'required|boolean',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'code' => $this->code ? strtoupper(str_replace(' ', '_', $this->code)) : null,
        ]);
    }
}
