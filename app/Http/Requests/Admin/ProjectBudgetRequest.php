<?php

namespace App\Http\Requests\Admin;

use App\Models\ProjectBudget;
use App\Models\ProjectBudgetItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // budget_code and version are deliberately absent — both are owned
            // by the service (version is per-project, not a global counter).
            // total_amount is likewise absent: it is summed from the lines.
            'project_id' => 'required|exists:projects,id',
            'title' => 'nullable|string|max:255',
            'budget_date' => 'required|date',
            'budget_status' => ['required', Rule::in(ProjectBudget::STATUSES)],
            'approved_by' => 'nullable|exists:admins,id',
            'approved_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.category' => ['required', Rule::in(ProjectBudgetItem::CATEGORIES)],
            'items.*.description' => 'nullable|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Select the project this budget belongs to.',
            'items.required' => 'A budget needs at least one budget line.',
            'items.min' => 'A budget needs at least one budget line.',
        ];
    }
}
