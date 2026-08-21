<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * A half-day request is confined to a single date, so force end_date to match
     * start_date before validation. Full-day requests are unchanged.
     */
    protected function prepareForValidation()
    {
        if ($this->input('duration_type') === 'half_day') {
            $this->merge(['end_date' => $this->input('start_date')]);
        }
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration_type' => 'required|in:full_day,half_day',
            'half_day_session' => 'nullable|required_if:duration_type,half_day|in:first_half,second_half',
            'reason' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:4096',
            'status' => 'required|boolean',
        ];
    }
}
