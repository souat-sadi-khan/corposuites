<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignSalaryComponentRequest extends FormRequest
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
            'amount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'employee_ids.required' => 'Select at least one employee to assign this component to.',
        ];
    }
}
