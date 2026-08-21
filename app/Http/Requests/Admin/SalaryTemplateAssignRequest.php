<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SalaryTemplateAssignRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'effective_date' => 'required|date',
            'status' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'employee_ids.required' => 'Select at least one employee to apply this template to.',
        ];
    }
}
