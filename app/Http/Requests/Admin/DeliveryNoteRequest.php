<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryNoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('delivery_note') ? $this->route('delivery_note')->id : null;

        return [
            'delivery_id' => ['required', 'exists:deliveries,id', Rule::unique('delivery_notes', 'delivery_id')->ignore($id)],
            'issued_date' => ['required', 'date'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'received_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'delivery_id.unique' => 'This delivery already has a delivery note.',
        ];
    }
}
