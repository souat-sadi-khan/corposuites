<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'requested_by' => ['nullable', 'exists:admins,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'required_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'request_status' => ['required', Rule::in(PurchaseRequest::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'items.*.product_id.distinct' => 'The same product cannot be added twice to a purchase request.',
        ];
    }
}
