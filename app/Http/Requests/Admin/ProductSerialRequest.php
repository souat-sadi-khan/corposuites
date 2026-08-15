<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductSerial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSerialRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('product_serial') ? $this->route('product_serial')->id : null;

        return [
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'serial_number' => [
                'required', 'string', 'max:255',
                Rule::unique('product_serials', 'serial_number')->where(fn ($q) => $q->where('product_id', $this->product_id))->ignore($id),
            ],
            'serial_status' => ['required', Rule::in(ProductSerial::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'serial_number.unique' => 'This serial number already exists for the selected product.',
        ];
    }
}
