<?php

namespace App\Http\Requests\Admin\DMS;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by every bulk action that only needs a set of item ids — trash,
 * restore, delete-forever, bulk download.
 */
class BulkIdsRequest extends FormRequest
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
        ];
    }
}
