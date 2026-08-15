<form class="ajax-form" method="POST" action="{{ route('admin.activities.update', $activity->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Activity</h5>
            <p>Update activity: {{ $activity->subject }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Type <span class="req">*</span></label>
                <select name="type" class="form-select" required>
                    <option value="call" {{ old('type', $activity->type) == 'call' ? 'selected' : '' }}>Call</option>
                    <option value="meeting" {{ old('type', $activity->type) == 'meeting' ? 'selected' : '' }}>Meeting</option>
                    <option value="email" {{ old('type', $activity->type) == 'email' ? 'selected' : '' }}>Email</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Due Date <span class="req">*</span></label>
                <input type="datetime-local" class="form-control" name="due_date" value="{{ old('due_date', optional($activity->due_date)->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Subject <span class="req">*</span></label>
                <input type="text" class="form-control" name="subject" value="{{ old('subject', $activity->subject) }}" required>
            </div>
            <div class="fm-field">
                <label>Related Lead</label>
                <select name="lead_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($leads as $lead)
                        <option value="{{ $lead->id }}" {{ old('lead_id', $activity->lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Contact</label>
                <select name="contact_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ old('contact_id', $activity->contact_id) == $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Company</label>
                <select name="company_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $activity->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Opportunity</label>
                <select name="opportunity_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($opportunities as $opportunity)
                        <option value="{{ $opportunity->id }}" {{ old('opportunity_id', $activity->opportunity_id) == $opportunity->id ? 'selected' : '' }}>{{ $opportunity->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Assigned To</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ old('assigned_to', $activity->assigned_to) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Activity Status</label>
                <select name="activity_status" class="form-select">
                    <option value="pending" {{ old('activity_status', $activity->activity_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ old('activity_status', $activity->activity_status) == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ old('activity_status', $activity->activity_status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $activity->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $activity->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $activity->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
