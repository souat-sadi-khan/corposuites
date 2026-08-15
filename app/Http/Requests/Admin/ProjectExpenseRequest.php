<?php

namespace App\Http\Requests\Admin;

use App\Models\ProjectExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'employee_id' => 'nullable|exists:employees,id',
            'title' => 'required|string|max:255',
            'expense_category' => ['required', Rule::in(ProjectExpense::CATEGORIES)],
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'is_billable' => 'nullable|boolean',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Select the project this cost was spent on.',
        ];
    }
}
