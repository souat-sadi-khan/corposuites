<?php

namespace App\Http\Requests\Admin;

use App\Models\AssetLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('asset_location')?->id;

        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('asset_locations', 'code')->ignore($id),
            ],
            'location_type' => ['required', Rule::in(AssetLocation::LOCATION_TYPES)],
            'department_id' => 'nullable|exists:departments,id',
            'building' => 'nullable|string|max:255',
            'floor' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This location code is already in use.',
        ];
    }
}
