<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Backs the "Generate Timesheet" form only — week_start_date is any date
 * inside the target week, normalized by the service to that week's Monday.
 */
class ProjectTimesheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'week_start_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Select whose timesheet this is for.',
            'week_start_date.required' => 'Pick any date inside the week to generate.',
        ];
    }
}
