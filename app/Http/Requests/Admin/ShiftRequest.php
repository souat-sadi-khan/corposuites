<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShiftRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('shift') ? $this->route('shift')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('shifts', 'name')->ignore($id)],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
