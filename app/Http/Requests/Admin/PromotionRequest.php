<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'from_designation' => 'nullable|string|max:100',
            'to_designation' => 'required|string|max:100',
            'from_salary' => 'nullable|numeric|min:0',
            'to_salary' => 'nullable|numeric|min:0',
            'promotion_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
