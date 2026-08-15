<?php

namespace App\Http\Requests\Admin;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => ['required', Rule::in(Activity::TYPES)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['required', 'date'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'assigned_to' => ['nullable', 'exists:admins,id'],
            'activity_status' => ['required', Rule::in(Activity::ACTIVITY_STATUSES)],
            'status' => ['required', 'boolean'],
        ];
    }
}
