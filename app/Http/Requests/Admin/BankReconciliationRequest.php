<?php

namespace App\Http\Requests\Admin;

use App\Models\BankReconciliation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankReconciliationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'finance_bank_account_id' => ['required', 'exists:finance_bank_accounts,id'],
            'statement_date' => ['required', 'date'],
            'statement_opening_balance' => ['required', 'numeric'],
            'statement_closing_balance' => ['required', 'numeric'],
            'reconciliation_status' => ['required', Rule::in(BankReconciliation::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.bank_transaction_id' => ['required', 'distinct', 'exists:bank_transactions,id'],
        ];
    }

    public function messages()
    {
        return [
            'items.*.bank_transaction_id.distinct' => 'The same transaction cannot be included twice in one reconciliation.',
        ];
    }
}
