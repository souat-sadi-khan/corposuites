<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorGroupRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('vendor_group') ? $this->route('vendor_group')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('vendor_groups', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ];
    }
}
