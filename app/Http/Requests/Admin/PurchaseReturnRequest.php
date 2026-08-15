<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseReturnRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'goods_receipt_id' => ['nullable', 'exists:goods_receipts,id'],
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'return_status' => ['required', Rule::in(PurchaseReturn::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.condition' => ['required', Rule::in(PurchaseReturnItem::CONDITIONS)],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'items.*.product_id.distinct' => 'The same product cannot be added twice to a return.',
        ];
    }
}
