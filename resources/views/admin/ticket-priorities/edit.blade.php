<form class="ajax-form" method="POST" action="{{ route('admin.ticket-priorities.update', $ticketPriority->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Ticket Priority</h5>
            <p>Update: {{ $ticketPriority->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $ticketPriority->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Maps To <span class="req">*</span></label>
                <select name="maps_to" class="form-select" required>
                    <option value="low" {{ $ticketPriority->maps_to === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $ticketPriority->maps_to === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $ticketPriority->maps_to === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ $ticketPriority->maps_to === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Color</label>
                <input type="color" class="form-control form-control-color" name="color" value="{{ old('color', $ticketPriority->color ?? '#6c757d') }}">
            </div>
            <div class="fm-field">
                <label>Sort Order</label>
                <input type="number" min="0" max="999" class="form-control" name="sort_order" value="{{ old('sort_order', $ticketPriority->sort_order) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $ticketPriority->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $ticketPriority->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $ticketPriority->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> "Maps To" tells the system which of the four fixed ticket priorities this level counts as, so filtering and reporting still work correctly.
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
