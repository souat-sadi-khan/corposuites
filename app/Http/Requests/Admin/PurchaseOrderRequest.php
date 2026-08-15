<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'purchase_request_id' => ['nullable', 'exists:purchase_requests,id'],
            'rfq_id' => ['nullable', 'exists:rfqs,id'],
            'supplier_quotation_id' => ['nullable', 'exists:supplier_quotations,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'order_status' => ['required', Rule::in(PurchaseOrder::STATUSES)],
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
            'items.*.product_id.distinct' => 'The same product cannot be added twice to a purchase order.',
        ];
    }
}
