<?php

namespace App\Http\Requests\Admin;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FinanceBankAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('finance_bank_account') ? $this->route('finance_bank_account')->id : null;

        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100', Rule::unique('finance_bank_accounts', 'account_number')->ignore($id)],
            'branch' => ['nullable', 'string', 'max:255'],
            'ifsc_swift_code' => ['nullable', 'string', 'max:50'],
            'currency' => ['required', 'string', 'max:10'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'chart_of_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if (!$this->chart_of_account_id) {
                return;
            }

            $account = ChartOfAccount::find($this->chart_of_account_id);

            if ($account && $account->is_group) {
                $validator->errors()->add('chart_of_account_id', 'Cannot link to a group/header account. Select a postable (non-group) account instead.');
            }
        });
    }
}
