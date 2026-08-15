<form class="ajax-form" method="POST" action="{{ route('admin.ticket-assignments.update', $ticketAssignment->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Ticket Assignment</h5>
            <p>{{ $ticketAssignment->ticket->ticket_number ?? '' }} — {{ $ticketAssignment->ticket->subject ?? 'Ticket removed' }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Ticket <span class="req">*</span></label>
                <select name="ticket_id" class="form-select select" required>
                    <option value="">Select ticket</option>
                    @foreach ($tickets as $ticket)
                        <option value="{{ $ticket->id }}" {{ $ticketAssignment->ticket_id == $ticket->id ? 'selected' : '' }}>{{ $ticket->ticket_number }} — {{ $ticket->subject }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Agent <span class="req">*</span></label>
                <select name="assigned_to" class="form-select select" required>
                    <option value="">Select agent</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}" {{ $ticketAssignment->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Assigned Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="assigned_date" value="{{ old('assigned_date', $ticketAssignment->assigned_date->toDateString()) }}" required>
            </div>
            <div class="fm-field">
                <label>Assignment State <span class="req">*</span></label>
                <select name="assignment_status" class="form-select" required>
                    <option value="assigned" {{ $ticketAssignment->assignment_status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="reassigned" {{ $ticketAssignment->assignment_status === 'reassigned' ? 'selected' : '' }}>Reassigned</option>
                    <option value="cancelled" {{ $ticketAssignment->assignment_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $ticketAssignment->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $ticketAssignment->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $ticketAssignment->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> A ticket can only have one active assignment at a time — mark the existing one Reassigned or Cancelled before assigning it to someone new.
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
