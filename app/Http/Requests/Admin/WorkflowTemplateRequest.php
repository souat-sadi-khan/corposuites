<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowTemplateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('workflow_template') ? $this->route('workflow_template')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('workflow_templates', 'name')->ignore($id)],
            'description' => 'nullable|string|max:1000',
            'approval_mode' => 'required|in:single,sequential,parallel',
            'status' => 'required|boolean',
        ];
    }
}
