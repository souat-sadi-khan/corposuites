<?php

namespace App\Http\Requests\Admin;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('expense_category') ? $this->route('expense_category')->id : null;

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('expense_categories', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_amount_per_claim' => ['nullable', 'numeric', 'min:0'],
            'receipt_required_above' => ['nullable', 'numeric', 'min:0'],
            'chart_of_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * Same "no posting to group/header accounts" rule Tax Rates and
     * Journal Entries already enforce for their own Chart of Accounts
     * links — a category should map to a real, postable ledger account.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (blank($this->chart_of_account_id)) {
                return;
            }

            $account = ChartOfAccount::find($this->chart_of_account_id);

            if ($account && $account->is_group) {
                $validator->errors()->add(
                    'chart_of_account_id',
                    'Cannot link to "' . $account->name . '" — it is a group/header account, not a postable one.'
                );
            }
        });
    }
}
