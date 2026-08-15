<form class="ajax-form" method="POST" action="{{ route('admin.email-communications.update', $emailCommunication->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Email Communication</h5>
            <p>Update entry: {{ $emailCommunication->subject }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Direction <span class="req">*</span></label>
                <select name="direction" class="form-select" required>
                    <option value="outbound" {{ old('direction', $emailCommunication->direction) == 'outbound' ? 'selected' : '' }}>Outbound</option>
                    <option value="inbound" {{ old('direction', $emailCommunication->direction) == 'inbound' ? 'selected' : '' }}>Inbound</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Sent At <span class="req">*</span></label>
                <input type="datetime-local" class="form-control" name="sent_at" value="{{ old('sent_at', optional($emailCommunication->sent_at)->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="fm-field">
                <label>From Email</label>
                <input type="email" class="form-control" name="from_email" value="{{ old('from_email', $emailCommunication->from_email) }}">
            </div>
            <div class="fm-field">
                <label>To Email</label>
                <input type="email" class="form-control" name="to_email" value="{{ old('to_email', $emailCommunication->to_email) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Subject <span class="req">*</span></label>
                <input type="text" class="form-control" name="subject" value="{{ old('subject', $emailCommunication->subject) }}" required>
            </div>
            <div class="fm-field">
                <label>Related Lead</label>
                <select name="lead_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($leads as $lead)
                        <option value="{{ $lead->id }}" {{ old('lead_id', $emailCommunication->lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Contact</label>
                <select name="contact_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ old('contact_id', $emailCommunication->contact_id) == $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Related Company</label>
                <select name="company_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $emailCommunication->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Body</label>
                <textarea class="form-control" name="body" rows="4">{{ old('body', $emailCommunication->body) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $emailCommunication->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $emailCommunication->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
