<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The plain edit form only ever touches notes and the archive status
 * toggle — dates, hours and the workflow state are all managed through the
 * generate/submit/approve/reject actions instead, so none of them appear
 * here.
 */
class ProjectTimesheetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }
}
