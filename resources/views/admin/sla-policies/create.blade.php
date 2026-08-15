<form class="ajax-form" method="POST" action="{{ route('admin.sla-policies.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add SLA Policy</h5>
            <p>Set response and resolution targets for a priority level</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Urgent Priority SLA" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Response Time (hours) <span class="req">*</span></label>
                <input type="number" step="0.5" min="0.5" class="form-control" name="response_time_hours" placeholder="e.g., 1" required>
            </div>
            <div class="fm-field">
                <label>Resolution Time (hours) <span class="req">*</span></label>
                <input type="number" step="0.5" min="0.5" class="form-control" name="resolution_time_hours" placeholder="e.g., 4" required>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Only one policy is allowed per priority level. New/edited tickets at that priority automatically pick up these targets, timed from when the ticket was created.
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
