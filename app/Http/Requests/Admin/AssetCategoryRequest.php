<?php

namespace App\Http\Requests\Admin;

use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('asset_category')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'name')->ignore($id),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('asset_categories', 'code')->ignore($id),
            ],
            'depreciation_method' => ['required', Rule::in(AssetCategory::DEPRECIATION_METHODS)],
            // Only meaningful when the category actually depreciates — a
            // "none" category has no life to spread cost over.
            'useful_life_years' => 'nullable|integer|min:1|max:100|required_unless:depreciation_method,none',
            'salvage_value_percent' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An asset category with this name already exists.',
            'code.unique' => 'This asset category code is already in use.',
            'useful_life_years.required_unless' => 'Useful life is required when a depreciation method is selected.',
            'salvage_value_percent.max' => 'Salvage value cannot exceed 100% of the asset cost.',
        ];
    }
}
