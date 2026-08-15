<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TerminationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'termination_date' => 'required|date',
            'type' => 'required|in:voluntary,involuntary',
            'reason' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
