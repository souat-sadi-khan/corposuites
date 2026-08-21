<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('attendance') ? $this->route('attendance')->id : null;

        return [
            'employee_id' => [
                'required', 'exists:employees,id',
                Rule::unique('attendances', 'employee_id')->where(fn($q) => $q->where('attendance_date', $this->attendance_date))->ignore($id),
            ],
            'attendance_date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'attendance_status' => 'required|in:present,absent,half_day,on_leave,late,early_leave',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'remarks' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
