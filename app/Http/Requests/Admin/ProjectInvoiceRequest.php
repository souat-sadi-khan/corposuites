<?php

namespace App\Http\Requests\Admin;

use App\Models\ProjectExpense;
use App\Models\ProjectInvoice;
use App\Models\ProjectInvoiceItem;
use App\Models\ProjectTimeEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // invoice_number is server-generated only.
            'project_id' => 'required|exists:projects,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'invoice_status' => ['required', Rule::in(ProjectInvoice::STATUSES)],
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.source_type' => ['required', Rule::in(ProjectInvoiceItem::SOURCE_TYPES)],
            'items.*.project_time_entry_id' => 'nullable|exists:project_time_entries,id|required_if:items.*.source_type,time_entry',
            'items.*.project_expense_id' => 'nullable|exists:project_expenses,id|required_if:items.*.source_type,expense',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Select the project this bill is for.',
            'items.required' => 'Add at least one line to bill.',
            'items.*.project_time_entry_id.required_if' => 'Select which time entry this line bills.',
            'items.*.project_expense_id.required_if' => 'Select which expense this line bills.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->project_id || ! is_array($this->items)) {
                return;
            }

            $invoiceId = $this->route('project_invoice')?->id;
            $projectId = (int) $this->project_id;

            foreach ($this->items as $index => $item) {
                $timeEntryId = $item['project_time_entry_id'] ?? null;
                $expenseId = $item['project_expense_id'] ?? null;

                if ($timeEntryId) {
                    $entry = ProjectTimeEntry::find($timeEntryId);

                    if (! $entry) {
                        continue;
                    }

                    if ((int) $entry->project_id !== $projectId) {
                        $validator->errors()->add("items.$index.project_time_entry_id", 'That time entry belongs to a different project.');
                    }

                    $billedElsewhere = ProjectInvoiceItem::where('project_time_entry_id', $timeEntryId)
                        ->when($invoiceId, fn ($q) => $q->where('project_invoice_id', '!=', $invoiceId))
                        ->whereHas('projectInvoice', fn ($q) => $q->where('invoice_status', '!=', 'cancelled'))
                        ->exists();

                    if ($billedElsewhere) {
                        $validator->errors()->add("items.$index.project_time_entry_id", 'That time entry has already been billed on another invoice.');
                    }
                }

                if ($expenseId) {
                    $expense = ProjectExpense::find($expenseId);

                    if (! $expense) {
                        continue;
                    }

                    if ((int) $expense->project_id !== $projectId) {
                        $validator->errors()->add("items.$index.project_expense_id", 'That expense belongs to a different project.');
                    }

                    $billedElsewhere = ProjectInvoiceItem::where('project_expense_id', $expenseId)
                        ->when($invoiceId, fn ($q) => $q->where('project_invoice_id', '!=', $invoiceId))
                        ->whereHas('projectInvoice', fn ($q) => $q->where('invoice_status', '!=', 'cancelled'))
                        ->exists();

                    if ($billedElsewhere) {
                        $validator->errors()->add("items.$index.project_expense_id", 'That expense has already been billed on another invoice.');
                    }
                }
            }
        });
    }
}
