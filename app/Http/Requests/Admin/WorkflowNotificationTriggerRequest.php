<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowNotificationTriggerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'workflow_definition_id' => 'required|exists:workflow_definitions,id',
            'event' => 'required|in:step_pending,approved,rejected,resubmitted,completed',
            'notify_type' => 'required|in:role,user,initiator,approver',
            'notify_id' => 'nullable|integer|required_if:notify_type,role,user',
            'template_message' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }
}
