<form class="ajax-form" method="POST" action="{{ route('admin.approval-delegations.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Approval Delegation</h5>
            <p>Route an approver's tasks to a delegate while they are away</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Delegator (away) <span class="req">*</span></label>
                <select name="delegator_admin_id" class="form-select select" required data-placeholder="Select admin">
                    <option value="">Select admin</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Delegate (acts for) <span class="req">*</span></label>
                <select name="delegate_admin_id" class="form-select select" required data-placeholder="Select admin">
                    <option value="">Select admin</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Starts On <span class="req">*</span></label>
                <input type="date" class="form-control" name="starts_on" required>
            </div>
            <div class="fm-field">
                <label>Ends On <span class="req">*</span></label>
                <input type="date" class="form-control" name="ends_on" required>
            </div>
            <div class="fm-field fm-full">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" placeholder="e.g., Annual leave" autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required
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
