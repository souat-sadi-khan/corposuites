<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('leave_type') ? $this->route('leave_type')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('leave_types', 'name')->ignore($id)],
            'days_allowed' => 'required|integer|min:0|max:365',
            'is_paid' => 'required|boolean',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
