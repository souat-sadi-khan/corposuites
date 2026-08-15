<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveBalanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('leave_balance') ? $this->route('leave_balance')->id : null;

        return [
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => [
                'required', 'exists:leave_types,id',
                Rule::unique('leave_balances', 'leave_type_id')
                    ->where(fn($q) => $q->where('employee_id', $this->employee_id)->where('year', $this->year))
                    ->ignore($id),
            ],
            'year' => 'required|integer|digits:4',
            'allocated_days' => 'required|numeric|min:0|max:365',
            'used_days' => 'required|numeric|min:0|max:365',
            'status' => 'required|boolean',
        ];
    }
}
