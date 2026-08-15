<?php

namespace App\Http\Requests\Admin;

use App\Models\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('ticket_status')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('ticket_statuses', 'name')->ignore($id)],
            'maps_to' => ['required', Rule::in(TicketStatus::MAPS_TO)],
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A ticket status with this name already exists.',
        ];
    }
}
