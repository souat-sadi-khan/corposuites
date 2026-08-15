<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderLevelRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('reorder_level') ? $this->route('reorder_level')->id : null;

        return [
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => [
                'nullable', 'exists:warehouses,id',
                Rule::unique('reorder_levels', 'warehouse_id')
                    ->where(function ($q) {
                        $q->where('product_id', $this->product_id);
                        if ($this->warehouse_id) {
                            $q->where('warehouse_id', $this->warehouse_id);
                        } else {
                            $q->whereNull('warehouse_id');
                        }
                    })
                    ->ignore($id),
            ],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'warehouse_id.unique' => 'A reorder level already exists for this product and warehouse (or "All Warehouses" if left blank).',
        ];
    }
}
