<form class="ajax-form" method="POST" action="{{ route('admin.tickets.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Create Ticket</h5>
            <p>Log a new support ticket</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Subject <span class="req">*</span></label>
                <input type="text" class="form-control" name="subject" placeholder="e.g., Cannot access invoice PDF" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category <span class="req">*</span></label>
                <select name="ticket_category_id" class="form-select select" required>
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source</label>
                <select name="source" class="form-select">
                    <option value="web" selected>Web</option>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                    <option value="portal">Customer Portal</option>
                    <option value="walk_in">Walk-in</option>
                </select>
            </div>

            <div class="fm-field">
                <label>Customer</label>
                <select name="customer_id" class="form-select select">
                    <option value="">Not linked to a customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->customer_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Raised By (Employee)</label>
                <select name="raised_by_employee_id" class="form-select select">
                    <option value="">Not an internal request</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Requester Name</label>
                <input type="text" class="form-control" name="requester_name" placeholder="Used when no customer/employee is linked">
            </div>
            <div class="fm-field">
                <label>Requester Email</label>
                <input type="email" class="form-control" name="requester_email">
            </div>
            <div class="fm-field">
                <label>Requester Phone</label>
                <input type="text" class="form-control" name="requester_phone">
            </div>

            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select ticket-priority-select" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Custom Priority</label>
                <select name="ticket_priority_id" class="form-select select ticket-custom-priority-select">
                    <option value="">None</option>
                    @foreach ($ticketPriorities as $ticketPriority)
                        <option value="{{ $ticketPriority->id }}" data-maps-to="{{ $ticketPriority->maps_to }}">{{ $ticketPriority->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Ticket State <span class="req">*</span></label>
                <select name="ticket_status" class="form-select ticket-status-select" required>
                    <option value="open" selected>Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="on_hold">On Hold</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Custom Status</label>
                <select name="ticket_status_id" class="form-select select ticket-custom-status-select">
                    <option value="">None</option>
                    @foreach ($ticketStatuses as $ticketStatus)
                        <option value="{{ $ticketStatus->id }}" data-maps-to="{{ $ticketStatus->maps_to }}">{{ $ticketStatus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="fm-field fm-full">
                <label>Description <span class="req">*</span></label>
                <textarea class="form-control" name="description" rows="3" required></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Internal Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The ticket number is issued automatically. Marking a ticket Resolved or Closed stamps the matching timestamp; moving it back clears it. Custom Status/Priority options are narrowed to the ones matching the selected Ticket State/Priority.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
