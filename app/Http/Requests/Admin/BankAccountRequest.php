<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('bank_account') ? $this->route('bank_account')->id : null;

        return [
            'employee_id' => 'required|exists:employees,id',
            'bank_name' => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'account_number' => [
                'required', 'string', 'max:50',
                Rule::unique('bank_accounts', 'account_number')->where(fn($q) => $q->where('employee_id', $this->employee_id))->ignore($id),
            ],
            'branch_name' => 'nullable|string|max:255',
            'ifsc_swift_code' => 'nullable|string|max:50',
            'is_primary' => 'required|boolean',
            'status' => 'required|boolean',
        ];
    }
}
