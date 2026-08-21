<?php

namespace App\Http\Requests\Admin;

use App\Models\SalaryStructure;
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
            'commission_sales_amount' => 'nullable|numeric|min:0',
            'status' => 'required|boolean',
        ];
    }

    /**
     * A commission-based employee's payroll can't be generated without the
     * sales figure their commission is calculated against.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (blank($this->employee_id) || filled($this->commission_sales_amount)) {
                return;
            }

            $structure = SalaryStructure::where('employee_id', $this->employee_id)
                ->active()
                ->orderByDesc('effective_date')
                ->first();

            if ($structure && $structure->pay_type === 'commission') {
                $validator->errors()->add(
                    'commission_sales_amount',
                    'This employee is on a commission-based salary structure — enter the sales amount to calculate their commission.'
                );
            }
        });
    }
}
