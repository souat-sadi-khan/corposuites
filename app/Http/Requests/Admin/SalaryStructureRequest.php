<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SalaryStructureRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'effective_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'status' => 'required|boolean',
            'components' => 'nullable|array',
            'components.*.salary_component_id' => 'required_with:components|exists:salary_components,id',
            'components.*.amount' => 'required_with:components|numeric|min:0',
        ];
    }
}
