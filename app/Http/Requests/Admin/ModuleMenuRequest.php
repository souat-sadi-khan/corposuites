<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ModuleMenuRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('module_menu')?->id;

        return [
            'module_id'  => 'required|exists:modules,id',
            'parent_id'  => 'nullable|exists:module_menus,id',
            'label'      => 'required|string|max:255',
            'name'       => [
                'required',
                'string',
                'max:255',
                Rule::unique('module_menus', 'name')->ignore($id),
            ],
            'icon'       => 'nullable|string|max:50',
            'route'      => 'nullable|string|max:255',
            'url'        => 'nullable|string|max:255',
            'permission' => 'nullable|string|max:255',
            'order'      => 'nullable|integer|min:0',
            'status'     => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
