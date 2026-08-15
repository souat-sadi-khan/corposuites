<?php

namespace App\Http\Requests\Admin;

use App\Models\AttendanceAdjustment;
use App\Models\EmployeeLoan;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowDefinitionRequest extends FormRequest
{
    /**
     * Supported approvable modules for the Workflow Definition Builder.
     * Keep in sync with App\Services\WorkflowDefinitionService::MODULE_MAP.
     */
    public const MODULE_MAP = [
        'leave_request' => LeaveRequest::class,
        'expense_claim' => ExpenseClaim::class,
        'attendance_adjustment' => AttendanceAdjustment::class,
        'employee_loan' => EmployeeLoan::class,
        'purchase_request' => PurchaseRequest::class,
    ];

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'module_key' => 'required|in:' . implode(',', array_keys(self::MODULE_MAP)),
            'name' => 'required|string|max:255',
            'approval_mode' => 'required|in:single,sequential,parallel',
            'workflow_template_id' => 'nullable|exists:workflow_templates,id',
            'status' => 'required|boolean',

            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.approval_type' => 'required|in:single,all_must_approve,any_one_approves',
            'steps.*.approvers' => 'required|array|min:1',
            'steps.*.approvers.*.approver_type' => 'required|in:role,user,designation',
            'steps.*.approvers.*.approver_id' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'steps.required' => 'At least one approval step is required.',
            'steps.*.approvers.required' => 'Each step must have at least one approver.',
        ];
    }
}
