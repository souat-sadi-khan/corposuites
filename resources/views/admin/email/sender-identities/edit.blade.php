<form class="ajax-form" method="POST" action="{{ route('admin.email.sender-identities.update', $identity) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Sender Identity</h5>
            <p>Update sender details for {{ $identity->email }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label for="provider_id" class="form-label">
                    Provider
                    <span class="text-danger">*</span>
                </label>
                <select name="provider_id" id="provider_id" class="form-select select" data-placeholder="Select Provider" data-parsley-errors-container="#role_id_error">
                    <option value="">Select Provider</option>
                    @foreach($providers as $provider)
                        <option {{ $identity->provider_id == $provider->id ? 'selected' : '' }} value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
                <span id="role_id_error"></span>
            </div>

            <div class="fm-field fm-full">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ $identity->email }}" required>
            </div>
            <div class="fm-field fm-full">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ $identity->name }}">
            </div>
            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_default" class="form-check-input" id="editDefaultSwitch" value="1" @checked($identity->is_default)>
                    <label class="form-check-label" for="editDefaultSwitch">Set as Default</label>
                </div>
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
            </button>
        </div>
    </div>
</form>
