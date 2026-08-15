<?php

namespace App\Http\Requests\Admin;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // project_code is deliberately absent — server-generated only,
            // exactly like Client.client_code / Vendor.vendor_code.
            'name' => 'required|string|max:255',
            // Required here even though the column is nullable: no project may
            // be filed without a client, but deleting a client must not take
            // its delivery history with it.
            'client_id' => 'required|exists:clients,id',
            'department_id' => 'nullable|exists:departments,id',
            'project_manager_id' => 'nullable|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'actual_end_date' => 'nullable|date|after_or_equal:start_date',
            'priority' => ['required', Rule::in(Project::PRIORITIES)],
            'project_status' => ['required', Rule::in(Project::STATUSES)],
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Select the client this project is delivered to.',
            'end_date.after_or_equal' => 'The planned end date cannot fall before the start date.',
            'actual_end_date.after_or_equal' => 'The actual end date cannot fall before the start date.',
            'progress_percent.max' => 'Progress cannot exceed 100%.',
        ];
    }
}
