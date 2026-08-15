<form class="ajax-form" method="POST" action="{{ route('admin.tickets.update', $ticket->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Ticket</h5>
            <p>Update: {{ $ticket->subject }} ({{ $ticket->ticket_number }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Subject <span class="req">*</span></label>
                <input type="text" class="form-control" name="subject" value="{{ old('subject', $ticket->subject) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category <span class="req">*</span></label>
                <select name="ticket_category_id" class="form-select select" required>
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ $ticket->ticket_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source</label>
                <select name="source" class="form-select">
                    <option value="web" {{ $ticket->source === 'web' ? 'selected' : '' }}>Web</option>
                    <option value="email" {{ $ticket->source === 'email' ? 'selected' : '' }}>Email</option>
                    <option value="phone" {{ $ticket->source === 'phone' ? 'selected' : '' }}>Phone</option>
                    <option value="portal" {{ $ticket->source === 'portal' ? 'selected' : '' }}>Customer Portal</option>
                    <option value="walk_in" {{ $ticket->source === 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                </select>
            </div>

            <div class="fm-field">
                <label>Customer</label>
                <select name="customer_id" class="form-select select">
                    <option value="">Not linked to a customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" {{ $ticket->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }} ({{ $customer->customer_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Raised By (Employee)</label>
                <select name="raised_by_employee_id" class="form-select select">
                    <option value="">Not an internal request</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $ticket->raised_by_employee_id == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Requester Name</label>
                <input type="text" class="form-control" name="requester_name" value="{{ old('requester_name', $ticket->requester_name) }}">
            </div>
            <div class="fm-field">
                <label>Requester Email</label>
                <input type="email" class="form-control" name="requester_email" value="{{ old('requester_email', $ticket->requester_email) }}">
            </div>
            <div class="fm-field">
                <label>Requester Phone</label>
                <input type="text" class="form-control" name="requester_phone" value="{{ old('requester_phone', $ticket->requester_phone) }}">
            </div>

            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select ticket-priority-select" required>
                    <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Custom Priority</label>
                <select name="ticket_priority_id" class="form-select select ticket-custom-priority-select">
                    <option value="">None</option>
                    @foreach ($ticketPriorities as $ticketPriority)
                        <option value="{{ $ticketPriority->id }}" data-maps-to="{{ $ticketPriority->maps_to }}" {{ $ticket->ticket_priority_id == $ticketPriority->id ? 'selected' : '' }}>{{ $ticketPriority->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Ticket State <span class="req">*</span></label>
                <select name="ticket_status" class="form-select ticket-status-select" required>
                    <option value="open" {{ $ticket->ticket_status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticket->ticket_status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="on_hold" {{ $ticket->ticket_status === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    <option value="resolved" {{ $ticket->ticket_status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticket->ticket_status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Custom Status</label>
                <select name="ticket_status_id" class="form-select select ticket-custom-status-select">
                    <option value="">None</option>
                    @foreach ($ticketStatuses as $ticketStatus)
                        <option value="{{ $ticketStatus->id }}" data-maps-to="{{ $ticketStatus->maps_to }}" {{ $ticket->ticket_status_id == $ticketStatus->id ? 'selected' : '' }}>{{ $ticketStatus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date" value="{{ old('due_date', $ticket->due_date?->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $ticket->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $ticket->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="fm-field fm-full">
                <label>Description <span class="req">*</span></label>
                <textarea class="form-control" name="description" rows="3" required>{{ old('description', $ticket->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Internal Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $ticket->notes) }}</textarea>
            </div>

            @if($ticket->first_response_due_at || $ticket->resolution_due_at)
                <div class="fm-field fm-full">
                    <label>SLA (read-only — resolved automatically from Priority)</label>
                    <div class="text-muted small">
                        @if($ticket->first_response_due_at)
                            Respond by {{ $ticket->first_response_due_at->format('d M Y, H:i') }}
                            @if($ticket->first_responded_at)
                                — <span class="text-success">responded {{ $ticket->first_responded_at->format('d M Y, H:i') }}</span>
                            @elseif($ticket->is_response_breached)
                                — <span class="text-danger">response overdue</span>
                            @endif
                            <br>
                        @endif
                        @if($ticket->resolution_due_at)
                            Resolve by {{ $ticket->resolution_due_at->format('d M Y, H:i') }}
                            @if($ticket->is_resolution_breached)
                                — <span class="text-danger">resolution overdue</span>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            @if($ticket->escalations->isNotEmpty())
                <div class="fm-field fm-full">
                    <label>Escalation History (read-only)</label>
                    <div class="text-muted small">
                        @foreach($ticket->escalations->sortByDesc('escalated_at') as $escalation)
                            {{ $escalation->escalated_at->format('d M Y, H:i') }} —
                            {{ $escalation->escalationRule->name ?? 'Rule removed' }}
                            @if($escalation->previous_priority && $escalation->new_priority && $escalation->previous_priority !== $escalation->new_priority)
                                ({{ ucfirst($escalation->previous_priority) }} → {{ ucfirst($escalation->new_priority) }})
                            @endif
                            @if($escalation->escalatedToAdmin)
                                — reassigned to {{ $escalation->escalatedToAdmin->name }}
                            @endif
                            <br>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i>
            @if($ticket->resolved_at)
                Resolved {{ $ticket->resolved_at->format('d M Y H:i') }}.
            @endif
            @if($ticket->closed_at)
                Closed {{ $ticket->closed_at->format('d M Y H:i') }}.
            @endif
            Moving the ticket state off Resolved/Closed clears the matching timestamp. Custom Status/Priority options are narrowed to the ones matching the selected Ticket State/Priority.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
