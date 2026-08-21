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

    /**
     * Normalise checkbox-style booleans and multi-selects before validation so the
     * form can send them as unchecked (absent) or as arrays of ids.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_paid' => $this->boolean('is_paid'),
            'status' => $this->boolean('status'),
            'allow_carry_forward' => $this->boolean('allow_carry_forward'),
            'allow_half_day' => $this->boolean('allow_half_day'),
            'requires_attachment' => $this->boolean('requires_attachment'),
            'is_encashable' => $this->boolean('is_encashable'),
        ]);
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

            // Accrual (B1)
            'accrual_method' => 'required|in:none,annual,monthly',

            // Carry-forward (B2)
            'allow_carry_forward' => 'required|boolean',
            'max_carry_forward' => 'nullable|numeric|min:0|max:365|required_if:allow_carry_forward,1',
            'carry_forward_expiry_months' => 'nullable|integer|min:0|max:24',

            // Eligibility (B3)
            'min_service_days' => 'required|integer|min:0|max:3650',
            'applicable_gender' => 'required|in:all,male,female,other',
            'applicable_employee_type_ids' => 'nullable|array',
            'applicable_employee_type_ids.*' => 'integer|exists:employee_types,id',
            'applicable_designation_ids' => 'nullable|array',
            'applicable_designation_ids.*' => 'integer|exists:designations,id',

            // Request rules (B4)
            'min_notice_days' => 'required|integer|min:0|max:365',
            'max_consecutive_days' => 'nullable|integer|min:1|max:365',
            'allow_half_day' => 'required|boolean',
            'requires_attachment' => 'required|boolean',
            'is_encashable' => 'required|boolean',
        ];
    }
}
