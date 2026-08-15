<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceAdjustmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'adjustment_date' => 'required|date',
            'requested_check_in' => 'nullable|date_format:H:i',
            'requested_check_out' => 'nullable|date_format:H:i|after:requested_check_in',
            'reason' => 'required|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
