<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('designation') ? $this->route('designation')->id : null;

        return [
            'department_id' => 'nullable|exists:departments,id',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('designations', 'name')->where(fn($q) => $q->where('department_id', $this->department_id))->ignore($id),
            ],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
