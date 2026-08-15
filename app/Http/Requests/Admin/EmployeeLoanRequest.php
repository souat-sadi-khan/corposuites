<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeLoanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'loan_amount' => 'required|numeric|min:0',
            'installments' => 'required|integer|min:1|max:120',
            'start_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
