<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LeadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'lead_status_id' => ['nullable', 'exists:lead_statuses,id'],
            'assigned_to' => ['nullable', 'exists:admins,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }
}
