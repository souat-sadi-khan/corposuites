<form class="ajax-form" method="POST" action="{{ route('admin.leads.update', $lead->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Lead</h5>
            <p>Update lead: {{ $lead->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $lead->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $lead->email) }}">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $lead->phone) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Company Name</label>
                <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $lead->company_name) }}">
            </div>
            <div class="fm-field">
                <label>Lead Source</label>
                <select name="lead_source_id" class="form-select select">
                    <option value="">No Source</option>
                    @foreach($leadSources as $leadSource)
                        <option value="{{ $leadSource->id }}" {{ old('lead_source_id', $lead->lead_source_id) == $leadSource->id ? 'selected' : '' }}>{{ $leadSource->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Pipeline Stage</label>
                <select name="lead_status_id" class="form-select select">
                    <option value="">No Stage</option>
                    @foreach($leadStatuses as $leadStatus)
                        <option value="{{ $leadStatus->id }}" {{ old('lead_status_id', $lead->lead_status_id) == $leadStatus->id ? 'selected' : '' }}>{{ $leadStatus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Assigned To</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ old('assigned_to', $lead->assigned_to) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="3">{{ old('notes', $lead->notes) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $lead->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $lead->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
                Updating...
            </button>
        </div>
    </div>
</form>
