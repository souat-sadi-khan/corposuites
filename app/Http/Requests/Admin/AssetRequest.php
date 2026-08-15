<?php

namespace App\Http\Requests\Admin;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('asset')?->id;

        return [
            'name' => 'required|string|max:255',
            // Required here even though the column is nullable — see the
            // migration comment: nullable exists only so deleting a
            // category cannot cascade away the asset register.
            'asset_category_id' => 'required|exists:asset_categories,id',
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('assets', 'serial_number')->ignore($id),
            ],
            'model_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'condition' => ['required', Rule::in(Asset::CONDITIONS)],
            'asset_status' => ['required', Rule::in(Asset::STATUSES)],
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
        // asset_code is deliberately absent — server-generated only.
    }

    public function messages(): array
    {
        return [
            'asset_category_id.required' => 'Every asset must be filed under an asset category.',
            'serial_number.unique' => 'Another asset is already registered with this serial number.',
        ];
    }
}
