<form class="ajax-form" method="POST" action="{{ route('admin.sla-policies.update', $slaPolicy->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit SLA Policy</h5>
            <p>Update: {{ $slaPolicy->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $slaPolicy->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select" required>
                    <option value="low" {{ $slaPolicy->priority === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $slaPolicy->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $slaPolicy->priority === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ $slaPolicy->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $slaPolicy->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $slaPolicy->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Response Time (hours) <span class="req">*</span></label>
                <input type="number" step="0.5" min="0.5" class="form-control" name="response_time_hours" value="{{ old('response_time_hours', $slaPolicy->response_time_hours) }}" required>
            </div>
            <div class="fm-field">
                <label>Resolution Time (hours) <span class="req">*</span></label>
                <input type="number" step="0.5" min="0.5" class="form-control" name="resolution_time_hours" value="{{ old('resolution_time_hours', $slaPolicy->resolution_time_hours) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $slaPolicy->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Only one policy is allowed per priority level. Editing these targets updates the due dates the next time a ticket at this priority is saved.
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
