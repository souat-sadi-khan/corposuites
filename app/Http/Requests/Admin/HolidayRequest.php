<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HolidayRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('holiday') ? $this->route('holiday')->id : null;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('holidays', 'name')->where(fn($q) => $q->where('date', $this->date))->ignore($id),
            ],
            'date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
