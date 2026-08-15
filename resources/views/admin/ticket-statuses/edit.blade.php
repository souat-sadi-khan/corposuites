<form class="ajax-form" method="POST" action="{{ route('admin.ticket-statuses.update', $ticketStatus->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Ticket Status</h5>
            <p>Update: {{ $ticketStatus->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $ticketStatus->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Maps To <span class="req">*</span></label>
                <select name="maps_to" class="form-select" required>
                    <option value="open" {{ $ticketStatus->maps_to === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticketStatus->maps_to === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="on_hold" {{ $ticketStatus->maps_to === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    <option value="resolved" {{ $ticketStatus->maps_to === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticketStatus->maps_to === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Color</label>
                <input type="color" class="form-control form-control-color" name="color" value="{{ old('color', $ticketStatus->color ?? '#6c757d') }}">
            </div>
            <div class="fm-field">
                <label>Sort Order</label>
                <input type="number" min="0" max="999" class="form-control" name="sort_order" value="{{ old('sort_order', $ticketStatus->sort_order) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $ticketStatus->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $ticketStatus->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $ticketStatus->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> "Maps To" tells the system which of the five fixed ticket states this status counts as, so overdue/closed logic still works correctly.
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
