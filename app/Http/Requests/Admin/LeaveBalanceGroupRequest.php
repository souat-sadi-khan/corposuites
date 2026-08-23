<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Backs the Leave Balance "Manage" master-detail form — one employee, one
 * year, any number of leave-type lines under it — for both store() (new
 * group) and update() (existing group, employee_id/year read-only in the
 * UI but still submitted as hidden fields, the same "locked scope field"
 * technique this project's other master-detail edit forms already use).
 */
class LeaveBalanceGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.leave_type_id' => [
                'required', 'integer', 'exists:leave_types,id', 'distinct',
            ],
            'items.*.allocated_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'items.*.used_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'items.*.carried_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'items.*.carry_expires_on' => ['nullable', 'date'],
            'items.*.status' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one leave type to this record.',
            'items.*.leave_type_id.distinct' => 'Each leave type can only appear once per employee per year.',
        ];
    }
}
