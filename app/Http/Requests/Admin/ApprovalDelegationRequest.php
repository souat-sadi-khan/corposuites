<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalDelegationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'status' => $this->boolean('status'),
        ]);
    }

    public function rules()
    {
        return [
            'delegator_admin_id' => 'required|integer|exists:admins,id|different:delegate_admin_id',
            'delegate_admin_id' => 'required|integer|exists:admins,id',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'reason' => 'nullable|string|max:255',
            'status' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'delegator_admin_id.different' => 'The delegator and delegate must be different admins.',
            'ends_on.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
