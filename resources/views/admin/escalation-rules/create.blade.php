<form class="ajax-form" method="POST" action="{{ route('admin.escalation-rules.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Escalation Rule</h5>
            <p>Define what happens when a ticket breaches its SLA</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Urgent Resolution Breach Escalation" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent" selected>Urgent</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Trigger <span class="req">*</span></label>
                <select name="trigger" class="form-select" required>
                    <option value="response_breach">Response Breach</option>
                    <option value="resolution_breach" selected>Resolution Breach</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Reassign To</label>
                <select name="escalate_to_admin_id" class="form-select select">
                    <option value="">No reassignment</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Bump Priority To</label>
                <select name="escalate_priority_to" class="form-select">
                    <option value="">No priority change</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
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
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Only one rule is allowed per priority + trigger combination, and at least one action (reassign and/or bump priority) is required. Escalation is a manual action on each ticket, not automatic.
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
