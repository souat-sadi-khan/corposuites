<?php

namespace App\Http\Requests\Admin;

use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuotationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'lead_id' => ['nullable', 'exists:leads,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'quotation_status' => ['required', Rule::in(Quotation::STATUSES)],
            'status' => ['required', 'boolean'],
        ];
    }
}
