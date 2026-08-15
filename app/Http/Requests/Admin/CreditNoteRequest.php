<?php

namespace App\Http\Requests\Admin;

use App\Models\CreditNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreditNoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
            'credit_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'credit_status' => ['required', Rule::in(CreditNote::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages()
    {
        return [
            'items.*.product_id.distinct' => 'The same product cannot be added twice to a credit note.',
        ];
    }
}
