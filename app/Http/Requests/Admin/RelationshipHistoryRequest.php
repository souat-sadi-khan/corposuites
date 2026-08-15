<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelationshipHistoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => ['required', Rule::in(['call', 'email', 'meeting', 'note', 'other'])],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'interaction_date' => ['required', 'date'],
            'lead_id' => ['nullable', 'required_without_all:contact_id,company_id', 'exists:leads,id'],
            'contact_id' => ['nullable', 'required_without_all:lead_id,company_id', 'exists:contacts,id'],
            'company_id' => ['nullable', 'required_without_all:lead_id,contact_id', 'exists:companies,id'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'lead_id.required_without_all' => 'Please relate this entry to a Lead, Contact, or Company.',
            'contact_id.required_without_all' => 'Please relate this entry to a Lead, Contact, or Company.',
            'company_id.required_without_all' => 'Please relate this entry to a Lead, Contact, or Company.',
        ];
    }
}
