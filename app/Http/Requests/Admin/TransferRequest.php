<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'from_department' => 'nullable|string|max:100',
            'to_department' => 'nullable|string|max:100',
            'from_designation' => 'nullable|string|max:100',
            'to_designation' => 'nullable|string|max:100',
            'transfer_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
