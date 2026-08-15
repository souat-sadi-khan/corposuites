<?php

namespace App\Http\Requests\Admin;

use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ticket_number is deliberately absent — server-generated only,
            // exactly like Project.project_code/Client.client_code.
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            // Required here even though the column is nullable: no ticket may
            // be filed without a category, but deleting a category must not
            // take the ticket register with it (same split Asset Register
            // uses for asset_category_id).
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'raised_by_employee_id' => 'nullable|exists:employees,id',
            'requester_name' => 'nullable|string|max:255',
            'requester_email' => 'nullable|email|max:255',
            'requester_phone' => 'nullable|string|max:30',
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'ticket_priority_id' => 'nullable|exists:ticket_priorities,id',
            'ticket_status' => ['required', Rule::in(Ticket::STATUSES)],
            'ticket_status_id' => 'nullable|exists:ticket_statuses,id',
            'source' => ['required', Rule::in(Ticket::SOURCES)],
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_category_id.required' => 'Select the category this ticket belongs to.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->ticket_status_id || ! $this->ticket_status) {
                return;
            }

            $ticketStatus = TicketStatus::find($this->ticket_status_id);

            // The custom status must count as the same fixed bucket the
            // ticket's own enum carries — the same "sub-classification's
            // nature must match the parent's own type" cross-check
            // ChartOfAccountRequest already runs for account_type_id.
            if ($ticketStatus && $ticketStatus->maps_to !== $this->ticket_status) {
                $validator->errors()->add(
                    'ticket_status_id',
                    'The selected Ticket Status ("' . $ticketStatus->name . '") maps to "' . $ticketStatus->maps_to_label . '", which does not match this ticket\'s state ("' . ucwords(str_replace('_', ' ', $this->ticket_status)) . '").'
                );
            }
        });

        $validator->after(function ($validator) {
            if (! $this->ticket_priority_id || ! $this->priority) {
                return;
            }

            $ticketPriority = TicketPriority::find($this->ticket_priority_id);

            // Same cross-consistency check as ticket_status_id above, just
            // for the priority side.
            if ($ticketPriority && $ticketPriority->maps_to !== $this->priority) {
                $validator->errors()->add(
                    'ticket_priority_id',
                    'The selected Ticket Priority ("' . $ticketPriority->name . '") maps to "' . $ticketPriority->maps_to_label . '", which does not match this ticket\'s priority ("' . ucfirst($this->priority) . '").'
                );
            }
        });
    }
}
