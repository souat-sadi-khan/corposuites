<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FollowUpRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'remind_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'assigned_to' => ['nullable', 'exists:admins,id'],
            'is_completed' => ['nullable', 'boolean'],
            'status' => ['required', 'boolean'],
        ];
    }
}
