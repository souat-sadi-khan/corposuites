<?php

namespace App\Http\Requests\Admin\DMS;

use Illuminate\Foundation\Http\FormRequest;

class MoveItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:dms_items,id',
            // null / omitted destination_id means "move to root".
            'destination_id' => 'nullable|integer|exists:dms_items,id',
        ];
    }
}
