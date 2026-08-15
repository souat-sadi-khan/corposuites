<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('lead_status') ? $this->route('lead_status')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('lead_statuses', 'name')->ignore($id)],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
