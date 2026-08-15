<form class="ajax-form" method="POST" action="{{ route('admin.escalation-rules.update', $escalationRule->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Escalation Rule</h5>
            <p>Update: {{ $escalationRule->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $escalationRule->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select" required>
                    <option value="low" {{ $escalationRule->priority === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $escalationRule->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $escalationRule->priority === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ $escalationRule->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Trigger <span class="req">*</span></label>
                <select name="trigger" class="form-select" required>
                    <option value="response_breach" {{ $escalationRule->trigger === 'response_breach' ? 'selected' : '' }}>Response Breach</option>
                    <option value="resolution_breach" {{ $escalationRule->trigger === 'resolution_breach' ? 'selected' : '' }}>Resolution Breach</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Reassign To</label>
                <select name="escalate_to_admin_id" class="form-select select">
                    <option value="">No reassignment</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}" {{ $escalationRule->escalate_to_admin_id == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Bump Priority To</label>
                <select name="escalate_priority_to" class="form-select">
                    <option value="" {{ ! $escalationRule->escalate_priority_to ? 'selected' : '' }}>No priority change</option>
                    <option value="low" {{ $escalationRule->escalate_priority_to === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $escalationRule->escalate_priority_to === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $escalationRule->escalate_priority_to === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ $escalationRule->escalate_priority_to === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $escalationRule->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $escalationRule->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $escalationRule->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Only one rule is allowed per priority + trigger combination, and at least one action (reassign and/or bump priority) is required.
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
