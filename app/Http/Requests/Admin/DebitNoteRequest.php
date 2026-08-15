<?php

namespace App\Http\Requests\Admin;

use App\Models\DebitNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebitNoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'purchase_invoice_id' => ['nullable', 'exists:purchase_invoices,id'],
            'debit_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'debit_status' => ['required', Rule::in(DebitNote::STATUSES)],
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
            'items.*.product_id.distinct' => 'The same product cannot be added twice to a debit note.',
        ];
    }
}
