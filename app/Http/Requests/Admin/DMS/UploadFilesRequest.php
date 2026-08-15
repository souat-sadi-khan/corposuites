<?php

namespace App\Http\Requests\Admin\DMS;

use Illuminate\Foundation\Http\FormRequest;

class UploadFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:dms_items,id',
            'files' => 'required|array|min:1',
            // No mime whitelist — this is a general-purpose file manager, not a
            // single-purpose upload form. 100MB per file is a sane base-panel
            // default; raise it per-project if needed.
            'files.*' => 'file|max:102400',
        ];
    }
}
