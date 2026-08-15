<?php

namespace App\Http\Requests\Admin;

use App\Models\ChartOfAccount;
use App\Models\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('tax_rate')?->id;

        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tax_rates', 'code')->ignore($id),
            ],
            'rate' => 'required|numeric|min:0|max:100',
            'tax_type' => ['required', Rule::in(TaxRate::TAX_TYPES)],
            'applies_to' => ['required', Rule::in(TaxRate::APPLIES_TO)],
            'sales_account_id' => 'nullable|exists:chart_of_accounts,id',
            'purchase_account_id' => 'nullable|exists:chart_of_accounts,id',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_compound' => 'nullable|boolean',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This tax code is already in use.',
            'rate.max' => 'A tax rate cannot exceed 100%.',
            'effective_to.after_or_equal' => 'The effective-to date must be on or after the effective-from date.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // A tax must post to a real, postable ledger account — a
            // group/header account can never receive a posting, the same
            // rule Journal Entries and Bank Accounts already enforce.
            foreach (['sales_account_id' => 'Sales', 'purchase_account_id' => 'Purchase'] as $field => $label) {
                if (! $this->input($field)) {
                    continue;
                }

                $account = ChartOfAccount::find($this->input($field));

                if ($account && $account->is_group) {
                    $validator->errors()->add(
                        $field,
                        'The ' . $label . ' tax account cannot be a group/header account — pick a postable account.'
                    );
                }
            }
        });
    }
}
