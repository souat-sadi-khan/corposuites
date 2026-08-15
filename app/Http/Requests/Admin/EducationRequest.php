<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EducationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'degree' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'start_year' => 'nullable|integer|digits:4',
            'end_year' => 'nullable|integer|digits:4|gte:start_year',
            'grade' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
