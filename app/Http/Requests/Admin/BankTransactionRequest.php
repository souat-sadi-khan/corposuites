<?php

namespace App\Http\Requests\Admin;

use App\Models\BankTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'finance_bank_account_id' => ['required', 'exists:finance_bank_accounts,id'],
            'transaction_date' => ['required', 'date'],
            'transaction_type' => ['required', Rule::in(BankTransaction::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'journal_entry_id' => ['nullable', 'exists:journal_entries,id'],
            'reconciled' => ['nullable', 'boolean'],
            'reconciled_date' => ['nullable', 'date'],
            'status' => ['required', 'boolean'],
        ];
    }
}
