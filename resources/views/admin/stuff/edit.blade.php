<form class="ajax-form" method="POST" action="{{ route('admin.stuff.update', $model->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Update User Information</h5>
            <p>Update user information for {{ $model->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label for="role_id" class="form-label">
                    Role
                    <span class="text-danger">*</span>
                </label>
                <select name="role_id" id="role_id" class="form-select select" data-placeholder="Select Role" data-parsley-errors-container="#role_id_error">
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(optional($model->roles->first())->id === $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <span id="role_id_error"></span>
            </div>

            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Sarah Williams" required autocomplete="off" value="{{ $model->name }}">
            </div>

            <div class="fm-field">
                <label class="form-label">
                    Email
                    <span class="text-danger">*</span>
                </label>
                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ $model->email }}" />
                <small class="text-muted">This email address is used for login to the system.</small>
            </div>

            <div class="fm-field">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" class="form-control" placeholder="Designation" value="{{ $model->designation }}" />
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note" style="margin:0;">
            <i class="ri-information-line"></i>
            Fields marked with * are required
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
