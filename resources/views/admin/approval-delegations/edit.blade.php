<form class="ajax-form" method="POST" action="{{ route('admin.approval-delegations.update', $approvalDelegation->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Approval Delegation</h5>
            <p>Update delegation for {{ $approvalDelegation->delegator->name ?? '-' }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        @php
            $delegatorId = old('delegator_admin_id', $approvalDelegation->delegator_admin_id);
            $delegateId = old('delegate_admin_id', $approvalDelegation->delegate_admin_id);
            $status = old('status', $approvalDelegation->status);
        @endphp
        <div class="fm-grid">
            <div class="fm-field">
                <label>Delegator (away) <span class="req">*</span></label>
                <select name="delegator_admin_id" class="form-select select" required data-placeholder="Select admin">
                    <option value="">Select admin</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ (string) $delegatorId === (string) $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Delegate (acts for) <span class="req">*</span></label>
                <select name="delegate_admin_id" class="form-select select" required data-placeholder="Select admin">
                    <option value="">Select admin</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ (string) $delegateId === (string) $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Starts On <span class="req">*</span></label>
                <input type="date" class="form-control" name="starts_on" value="{{ old('starts_on', optional($approvalDelegation->starts_on)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Ends On <span class="req">*</span></label>
                <input type="date" class="form-control" name="ends_on" value="{{ old('ends_on', optional($approvalDelegation->ends_on)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" value="{{ old('reason', $approvalDelegation->reason) }}" placeholder="e.g., Annual leave" autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ (string) $status === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive</option>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
