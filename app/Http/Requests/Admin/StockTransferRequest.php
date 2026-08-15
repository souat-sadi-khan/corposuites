<?php

namespace App\Http\Requests\Admin;

use App\Models\StockTransfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockTransferRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'from_warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'to_warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'transfer_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'transfer_status' => ['required', Rule::in(StockTransfer::STATUSES)],
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
            'from_warehouse_id.different' => 'Source and destination warehouses must be different.',
            'items.*.product_id.distinct' => 'The same product cannot be added twice to a stock transfer.',
        ];
    }
}
