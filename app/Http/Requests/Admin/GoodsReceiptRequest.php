<?php

namespace App\Http\Requests\Admin;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoodsReceiptRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'received_by' => ['nullable', 'exists:admins,id'],
            'received_date' => ['required', 'date'],
            'receipt_status' => ['required', Rule::in(GoodsReceipt::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0.01'],
            'items.*.condition' => ['required', Rule::in(GoodsReceiptItem::CONDITIONS)],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'items.*.product_id.distinct' => 'The same product cannot be added twice to a goods receipt.',
        ];
    }
}
