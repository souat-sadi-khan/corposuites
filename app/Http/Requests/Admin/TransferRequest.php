<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'from_department' => 'nullable|string|max:100',
            'to_department' => 'nullable|string|max:100',
            'from_designation' => 'nullable|string|max:100',
            'to_designation' => 'nullable|string|max:100',
            'transfer_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $employee = \App\Models\Employee::find($this->input('employee_id'));
            if (!$employee) return;
            $currentDepartment = $employee->department?->name;
            $currentDesignation = $employee->designation?->name;
            if ($this->input('to_department') === $currentDepartment && $this->input('to_designation') === $currentDesignation) {
                $validator->errors()->add('to_department', 'Choose a different department or designation; this employee is already assigned to the selected department and designation.');
            }
        }];
    }
}
