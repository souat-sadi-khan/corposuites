<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductBatchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('product_batch') ? $this->route('product_batch')->id : null;

        return [
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'batch_number' => [
                'required', 'string', 'max:100',
                Rule::unique('product_batches', 'batch_number')
                    ->where(fn ($q) => $q->where('product_id', $this->product_id)->where('warehouse_id', $this->warehouse_id))
                    ->ignore($id),
            ],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:manufacturing_date'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'batch_number.unique' => 'This batch number already exists for the selected product and warehouse.',
        ];
    }
}
