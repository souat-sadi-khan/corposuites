<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('unit') ? $this->route('unit')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->ignore($id)],
            'short_code' => ['required', 'string', 'max:20', Rule::unique('units', 'short_code')->ignore($id)],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
