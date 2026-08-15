<?php

namespace App\Http\Requests\Admin;

use App\Models\Budget;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // budget_code, version and total_amount are deliberately absent
            // — the first two are owned by the service (version is per
            // period, not a global counter), the total is summed from the
            // lines.
            'title' => 'nullable|string|max:255',
            'period_type' => ['required', Rule::in(Budget::PERIOD_TYPES)],
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'budget_status' => ['required', Rule::in(Budget::STATUSES)],
            'approved_by' => 'nullable|exists:admins,id',
            'approved_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.chart_of_account_id' => ['required', 'distinct', 'exists:chart_of_accounts,id'],
            'items.*.planned_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'period_end.after_or_equal' => 'The period end date must be on or after the period start date.',
            'items.required' => 'A budget needs at least one budget line.',
            'items.min' => 'A budget needs at least one budget line.',
            'items.*.chart_of_account_id.distinct' => 'The same account cannot appear on more than one line of this budget.',
        ];
    }

    /**
     * A budget can only allocate planned spend to postable (non-group)
     * accounts — the same "no posting to group/header accounts" rule
     * Journal Entries already enforces, applied here to budget lines.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            if (! is_array($items) || empty($items)) {
                return;
            }

            $accountIds = collect($items)->pluck('chart_of_account_id')->filter()->unique();

            if ($accountIds->isEmpty()) {
                return;
            }

            $groupAccounts = ChartOfAccount::whereIn('id', $accountIds)->where('is_group', true)->pluck('name');

            if ($groupAccounts->isNotEmpty()) {
                $validator->errors()->add('items', 'Cannot allocate budget to a group/header account: ' . $groupAccounts->implode(', ') . '. Select a postable (non-group) account instead.');
            }
        });
    }
}
