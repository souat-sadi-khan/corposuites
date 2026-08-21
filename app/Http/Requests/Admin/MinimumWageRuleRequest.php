<?php

namespace App\Http\Requests\Admin;

use App\Models\MinimumWageRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinimumWageRuleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('minimum_wage_rule') ? $this->route('minimum_wage_rule')->id : null;

        return [
            'country' => ['required', 'string', 'max:100'],
            'state' => [
                'nullable', 'string', 'max:100',
                Rule::unique('minimum_wage_rules', 'state')
                    ->where(function ($q) {
                        $q->where('country', $this->country)
                            ->where('wage_type', $this->wage_type)
                            ->where('effective_date', $this->effective_date);

                        if ($this->state) {
                            $q->where('state', $this->state);
                        } else {
                            $q->whereNull('state');
                        }
                    })
                    ->ignore($id),
            ],
            'wage_type' => ['required', Rule::in(MinimumWageRule::WAGE_TYPES)],
            'minimum_wage' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'state.unique' => 'A minimum wage rule already exists for this country/state, wage type, and effective date.',
        ];
    }
}
