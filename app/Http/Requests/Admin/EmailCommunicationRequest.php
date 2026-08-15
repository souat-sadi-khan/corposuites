<?php

namespace App\Http\Requests\Admin;

use App\Models\EmailCommunication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailCommunicationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'direction' => ['required', Rule::in(EmailCommunication::DIRECTIONS)],
            'from_email' => ['nullable', 'email', 'max:255'],
            'to_email' => ['nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'sent_at' => ['required', 'date'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'status' => ['required', 'boolean'],
        ];
    }
}
