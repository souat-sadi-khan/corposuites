<?php

namespace App\Http\Requests\Admin;

use App\Models\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('account_type') ? $this->route('account_type')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('account_types', 'name')->ignore($id)],
            'nature' => ['required', Rule::in(AccountType::NATURES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ];
    }
}
