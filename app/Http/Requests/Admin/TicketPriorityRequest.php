<?php

namespace App\Http\Requests\Admin;

use App\Models\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('ticket_priority')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('ticket_priorities', 'name')->ignore($id)],
            'maps_to' => ['required', Rule::in(TicketPriority::MAPS_TO)],
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A ticket priority with this name already exists.',
        ];
    }
}
