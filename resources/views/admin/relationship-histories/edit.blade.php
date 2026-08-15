<form class="ajax-form" method="POST" action="{{ route('admin.relationship-histories.update', $relationshipHistory->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Relationship History Entry</h5>
            <p>Update entry: {{ $relationshipHistory->subject }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Type <span class="req">*</span></label>
                <select name="type" class="form-select" required>
                    <option value="call" {{ old('type', $relationshipHistory->type) == 'call' ? 'selected' : '' }}>Call</option>
                    <option value="email" {{ old('type', $relationshipHistory->type) == 'email' ? 'selected' : '' }}>Email</option>
                    <option value="meeting" {{ old('type', $relationshipHistory->type) == 'meeting' ? 'selected' : '' }}>Meeting</option>
                    <option value="note" {{ old('type', $relationshipHistory->type) == 'note' ? 'selected' : '' }}>Note</option>
                    <option value="other" {{ old('type', $relationshipHistory->type) == 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="interaction_date" value="{{ old('interaction_date', optional($relationshipHistory->interaction_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Subject <span class="req">*</span></label>
                <input type="text" class="form-control" name="subject" value="{{ old('subject', $relationshipHistory->subject) }}" required>
            </div>
            <div class="fm-field">
                <label>Related Lead</label>
                <select name="lead_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($leads as $lead)
                        <option value="{{ $lead->id }}" {{ old('lead_id', $relationshipHistory->lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Contact</label>
                <select name="contact_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ old('contact_id', $relationshipHistory->contact_id) == $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Related Company</label>
                <select name="company_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $relationshipHistory->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $relationshipHistory->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $relationshipHistory->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $relationshipHistory->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Relate this entry to at least one Lead, Contact, or Company
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
