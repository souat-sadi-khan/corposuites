<?php

namespace App\Http\Requests\Admin;

use App\Models\SalaryTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryTemplateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('salary_template') ? $this->route('salary_template')->id : null;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('salary_templates', 'name')->ignore($id),
            ],
            'pay_type' => ['required', Rule::in(SalaryTemplate::PAY_TYPES)],
            // Same meaning-per-pay_type as SalaryStructureRequest: fixed monthly
            // amount, per-day rate, or a commission percentage (capped at 100).
            'basic_salary' => array_filter([
                'required',
                'numeric',
                'min:0',
                $this->input('pay_type') === 'commission' ? 'max:100' : null,
            ]),
            'description' => 'nullable|string',
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
