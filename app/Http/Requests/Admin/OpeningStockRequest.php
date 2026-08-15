<?php

namespace App\Http\Requests\Admin;

use App\Models\OpeningStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpeningStockRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'warehouse_location_id' => ['nullable', 'exists:warehouse_locations,id'],
            'opening_date' => ['required', 'date'],
            'entry_status' => ['required', Rule::in(OpeningStock::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'items.*.product_id.distinct' => 'The same product cannot be added twice to an opening stock entry.',
        ];
    }
}
