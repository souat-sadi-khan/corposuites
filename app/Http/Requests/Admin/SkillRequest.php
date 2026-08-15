<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkillRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('skill') ? $this->route('skill')->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('skills', 'name')->ignore($id)],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
