<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('employee') ? $this->route('employee')->id : null;

        return [
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($id)],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($id)],
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'date_of_joining' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'address' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:2048',
            'employee_type_id' => 'nullable|exists:employee_types,id',
            'employment_status_id' => 'nullable|exists:employment_statuses,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'status' => 'required|boolean',
        ];
    }
}
