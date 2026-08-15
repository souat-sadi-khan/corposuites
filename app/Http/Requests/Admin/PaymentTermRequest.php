<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentTermRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('payment_term') ? $this->route('payment_term')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_terms', 'name')->ignore($id)],
            'days' => ['required', 'integer', 'min:0'],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
