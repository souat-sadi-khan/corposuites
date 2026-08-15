<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('payroll') ? $this->route('payroll')->id : null;

        return [
            'employee_id' => [
                'required', 'exists:employees,id',
                Rule::unique('payrolls', 'employee_id')
                    ->where(fn($q) => $q->where('month', $this->month)->where('year', $this->year))
                    ->ignore($id),
            ],
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|digits:4',
            'status' => 'required|boolean',
        ];
    }
}
