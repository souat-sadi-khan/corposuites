<form class="ajax-form" method="POST" action="{{ route('admin.contacts.update', $contact->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Contact</h5>
            <p>Update contact: {{ $contact->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $contact->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $contact->email) }}">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $contact->phone) }}">
            </div>
            <div class="fm-field">
                <label>Job Title</label>
                <input type="text" class="form-control" name="job_title" value="{{ old('job_title', $contact->job_title) }}">
            </div>
            <div class="fm-field">
                <label>Company Name</label>
                <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $contact->company_name) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Related Lead</label>
                <select name="lead_id" class="form-select select">
                    <option value="">No Lead</option>
                    @foreach($leads as $lead)
                        <option data-desc="{{ $lead->email }}" value="{{ $lead->id }}" {{ old('lead_id', $contact->lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="3">{{ old('notes', $contact->notes) }}</textarea>
            </div>
            <input type="hidden" name="status" value="{{ $contact->status }}">
            {{-- <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $contact->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $contact->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div> --}}
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
