<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseLocationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('warehouse_location') ? $this->route('warehouse_location')->id : null;

        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('warehouse_locations', 'code')->where(fn ($q) => $q->where('warehouse_id', $this->warehouse_id))->ignore($id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'code.unique' => 'This location code already exists for the selected warehouse.',
        ];
    }
}
