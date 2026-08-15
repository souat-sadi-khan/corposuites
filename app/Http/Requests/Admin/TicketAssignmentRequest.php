<?php

namespace App\Http\Requests\Admin;

use App\Models\Ticket;
use App\Models\TicketAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_id' => 'required|exists:tickets,id',
            'assigned_to' => 'required|exists:admins,id',
            'assigned_date' => 'required|date',
            'assignment_status' => ['required', Rule::in(TicketAssignment::STATUSES)],
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('assignment_status') !== 'assigned' || ! $this->input('ticket_id')) {
                return;
            }

            $id = $this->route('ticket_assignment')?->id;

            // A ticket can only have one active handler at a time, so an
            // open assignment blocks another. Not expressible as a DB
            // constraint (it depends on the enum value of *other* rows), so
            // it lives here — the same app-level cross-record guard
            // `AssetAssignmentRequest`'s one-holder-at-a-time check uses.
            $alreadyAssigned = TicketAssignment::where('ticket_id', $this->input('ticket_id'))
                ->where('assignment_status', 'assigned')
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($alreadyAssigned) {
                $ticket = Ticket::find($this->input('ticket_id'));

                $validator->errors()->add(
                    'ticket_id',
                    'Ticket ' . ($ticket->ticket_number ?? '') . ' is already assigned to someone. Mark the existing assignment reassigned or cancelled first.'
                );
            }
        });
    }
}
