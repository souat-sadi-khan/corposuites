<?php

namespace App\Http\Requests\Admin;

use App\Models\AccountType;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChartOfAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('chart_of_account') ? $this->route('chart_of_account')->id : null;

        $parentIdRules = ['nullable', 'exists:chart_of_accounts,id'];
        if ($id) {
            $parentIdRules[] = Rule::notIn([$id]);
        }

        return [
            'parent_id' => $parentIdRules,
            'code' => ['required', 'string', 'max:50', Rule::unique('chart_of_accounts', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', Rule::in(ChartOfAccount::ACCOUNT_TYPES)],
            'account_type_id' => ['nullable', 'exists:account_types,id'],
            'is_group' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'parent_id.not_in' => 'An account cannot be its own parent.',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $chartOfAccount = $this->route('chart_of_account');

            if (!$chartOfAccount || !$this->parent_id) {
                return;
            }

            if (in_array((int) $this->parent_id, $chartOfAccount->descendantIds(), true)) {
                $validator->errors()->add('parent_id', 'An account cannot be moved under one of its own sub-accounts.');
            }
        });

        $validator->after(function ($validator) {
            if (!$this->account_type_id || !$this->account_type) {
                return;
            }

            $accountType = AccountType::find($this->account_type_id);

            if ($accountType && $accountType->nature !== $this->account_type) {
                $validator->errors()->add('account_type_id', 'The selected Account Type\'s nature ("' . ucfirst($accountType->nature) . '") does not match this account\'s Account Type ("' . ucfirst($this->account_type) . '").');
            }
        });
    }
}
