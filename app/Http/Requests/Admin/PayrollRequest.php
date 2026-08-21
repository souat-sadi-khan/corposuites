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
            'occurrence_counts' => 'nullable|array',
            'occurrence_counts.*' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
        ];
    }

    /**
     * A commission-based employee's payroll can't be generated without the
     * sales figure their commission is calculated against, and a per-
     * occurrence component (e.g. "$10 per late day") can't be calculated
     * without how many times it actually happened this period.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (blank($this->employee_id)) {
                return;
            }

            $structure = SalaryStructure::where('employee_id', $this->employee_id)
                ->active()
                ->orderByDesc('effective_date')
                ->with('items.salaryComponent')
                ->first();

            if (!$structure) {
                return;
            }

            if ($structure->pay_type === 'commission' && blank($this->commission_sales_amount)) {
                $validator->errors()->add(
                    'commission_sales_amount',
                    'This employee is on a commission-based salary structure — enter the sales amount to calculate their commission.'
                );
            }

            $occurrenceCounts = (array) $this->input('occurrence_counts', []);

            foreach ($structure->items as $item) {

                if ($item->salaryComponent->calculation_type !== 'per_occurrence') {
                    continue;
                }

                if (!array_key_exists($item->salary_component_id, $occurrenceCounts) || $occurrenceCounts[$item->salary_component_id] === '' || $occurrenceCounts[$item->salary_component_id] === null) {
                    $validator->errors()->add(
                        'occurrence_counts.' . $item->salary_component_id,
                        'Enter how many times "' . $item->salaryComponent->name . '" occurred this period (enter 0 if it never happened).'
                    );
                }
            }
        });
    }
}
