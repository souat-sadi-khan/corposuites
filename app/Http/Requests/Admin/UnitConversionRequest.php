<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitConversionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('unit_conversion') ? $this->route('unit_conversion')->id : null;

        return [
            'from_unit_id' => [
                'required', 'exists:units,id', 'different:to_unit_id',
            ],
            'to_unit_id' => [
                'required', 'exists:units,id',
                Rule::unique('unit_conversions', 'to_unit_id')
                    ->where(fn($q) => $q->where('from_unit_id', $this->from_unit_id))
                    ->ignore($id),
            ],
            'conversion_factor' => ['required', 'numeric', 'gt:0'],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'from_unit_id.different' => 'From Unit and To Unit must be different.',
            'to_unit_id.unique' => 'A conversion between these two units already exists.',
        ];
    }
}
