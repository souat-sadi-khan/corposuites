<?php

namespace App\Http\Requests\Admin;

use App\Models\Rfq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RfqRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'purchase_request_id' => ['nullable', 'exists:purchase_requests,id'],
            'rfq_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:rfq_date'],
            'rfq_status' => ['required', Rule::in(Rfq::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'vendor_ids' => ['required', 'array', 'min:1'],
            'vendor_ids.*' => ['distinct', 'exists:vendors,id'],
        ];
    }

    public function messages()
    {
        return [
            'items.*.product_id.distinct' => 'The same product cannot be added twice to an RFQ.',
            'vendor_ids.required' => 'Select at least one vendor to send this RFQ to.',
        ];
    }
}
