<form class="ajax-form" method="POST" action="{{ route('admin.clients.update', $client->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Client</h5>
            <p>Update: {{ $client->name }} ({{ $client->client_code }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Client Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $client->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Client Type <span class="req">*</span></label>
                <select name="client_type" class="form-select client-type-select" required>
                    <option value="company" {{ $client->client_type === 'company' ? 'selected' : '' }}>Company</option>
                    <option value="individual" {{ $client->client_type === 'individual' ? 'selected' : '' }}>Individual</option>
                </select>
            </div>
            <div class="fm-field client-company-field">
                <label>Company Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $client->company_name) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Contact Person</label>
                <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $client->email) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $client->phone) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Website</label>
                <input type="text" class="form-control" name="website" value="{{ old('website', $client->website) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Tax Number</label>
                <input type="text" class="form-control" name="tax_number" value="{{ old('tax_number', $client->tax_number) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>City</label>
                <input type="text" class="form-control" name="city" value="{{ old('city', $client->city) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Country</label>
                <input type="text" class="form-control" name="country" value="{{ old('country', $client->country) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $client->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $client->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2">{{ old('address', $client->address) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $client->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The client code ({{ $client->client_code }}) is fixed and cannot be changed.
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
