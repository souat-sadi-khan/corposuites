<?php

namespace App\Http\Requests\Admin;

use App\Models\SalaryStructure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'pay_type' => ['required', Rule::in(SalaryStructure::PAY_TYPES)],
            'effective_date' => 'required|date',
            // For "monthly" this is the fixed basic salary, for "daily" it's the
            // per-day rate, and for "commission" it's the commission percentage
            // (capped at 100) — the label/meaning switches client-side per pay_type.
            'basic_salary' => array_filter([
                'required',
                'numeric',
                'min:0',
                $this->input('pay_type') === 'commission' ? 'max:100' : null,
            ]),
            'status' => 'required|boolean',
            'components' => 'nullable|array',
            'components.*.salary_component_id' => 'required_with:components|exists:salary_components,id',
            'components.*.amount' => 'required_with:components|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'basic_salary.max' => 'Commission rate cannot exceed 100%.',
        ];
    }
}
