<?php

namespace App\Http\Requests\Admin;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class JournalEntryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'entry_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'narration' => ['nullable', 'string', 'max:2000'],
            'entry_status' => ['required', Rule::in(JournalEntry::STATUSES)],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:2'],
            'items.*.chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'items.*.debit' => ['nullable', 'numeric', 'min:0'],
            'items.*.credit' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages()
    {
        return [
            'items.min' => 'A journal entry needs at least 2 lines (one debit, one credit) to balance.',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            if (!is_array($items) || empty($items)) {
                return;
            }

            $totalDebit = 0;
            $totalCredit = 0;
            $accountIds = [];

            foreach ($items as $index => $item) {
                $debit = (float) ($item['debit'] ?? 0);
                $credit = (float) ($item['credit'] ?? 0);

                // Every line must be either a debit OR a credit, never both, never neither.
                if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                    $validator->errors()->add("items.{$index}.debit", 'Each line must have either a debit or a credit amount (not both, not neither).');
                }

                $totalDebit += $debit;
                $totalCredit += $credit;

                if (!empty($item['chart_of_account_id'])) {
                    $accountIds[] = $item['chart_of_account_id'];
                }
            }

            // Double-entry rule: total debits must equal total credits.
            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $validator->errors()->add('items', 'This journal entry is not balanced — total debit (' . number_format($totalDebit, 2) . ') must equal total credit (' . number_format($totalCredit, 2) . ').');
            }

            // Journal entries can only post to non-group (postable) accounts.
            if (!empty($accountIds)) {
                $groupAccounts = ChartOfAccount::whereIn('id', array_unique($accountIds))->where('is_group', true)->pluck('name');

                if ($groupAccounts->isNotEmpty()) {
                    $validator->errors()->add('items', 'Cannot post to a group/header account: ' . $groupAccounts->implode(', ') . '. Select a postable (non-group) account instead.');
                }
            }
        });
    }
}
