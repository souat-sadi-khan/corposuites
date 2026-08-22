<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkGeneratePayrollRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Every filter here is optional — leaving all of them blank targets
     * every active employee, which is exactly the "generate for all"
     * behavior this form exists for. Per-employee eligibility (already
     * has a payroll this period, no active structure, commission-based,
     * per-occurrence components) is resolved and skipped individually by
     * PayrollService::bulkGenerate(), not validated here, since none of
     * it is knowable until the employee set is actually resolved.
     */
    public function rules()
    {
        return [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|digits:4',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'employment_status_id' => 'nullable|exists:employment_statuses,id',
            'employee_type_id' => 'nullable|exists:employee_types,id',
            'gender' => 'nullable|in:male,female,other',
        ];
    }
}
