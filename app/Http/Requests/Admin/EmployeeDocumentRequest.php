<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeDocumentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'employee_id' => 'required|exists:employees,id',
            'title' => 'required|string|max:255',
            'file' => ($isUpdate ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'expiry_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
