<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResignationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'resignation_date' => 'required|date',
            'last_working_date' => 'nullable|date|after_or_equal:resignation_date',
            'notice_period_days' => 'nullable|integer|min:0|max:365',
            'reason' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
