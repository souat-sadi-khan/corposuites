<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('workflow_status') ? $this->route('workflow_status')->id : null;

        return [
            'workflow_definition_id' => 'required|exists:workflow_definitions,id',
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workflow_statuses', 'key')
                    ->where('workflow_definition_id', $this->workflow_definition_id)
                    ->ignore($id),
            ],
            'label' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
            'is_terminal' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
