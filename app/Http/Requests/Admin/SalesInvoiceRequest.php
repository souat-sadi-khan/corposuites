<?php

namespace App\Http\Requests\Admin;

use App\Models\SalesInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'assigned_to' => ['nullable', 'exists:admins,id'],
            'sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'payment_term_id' => ['nullable', 'exists:payment_terms,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'invoice_status' => ['required', Rule::in(SalesInvoice::STATUSES)],
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
            'items.*.product_id.distinct' => 'The same product cannot be added twice to an invoice.',
        ];
    }
}
