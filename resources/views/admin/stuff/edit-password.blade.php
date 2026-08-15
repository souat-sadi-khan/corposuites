<form class="ajax-form" method="POST" action="{{ route('admin.stuff.update.password', $model->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">{{ t('user.update_password_header') . ' '. $model->name }}</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="row fm-grid">
            <div class="fm-field fm-full">
                <label class="form-label" for="newPassword">New Password</label>
                <input name="password" required placeholder="New password" id="newPassword" class="form-control form-control-lg" type="password">
                <small class="text-muted form-text">Minimum 8 Characters</small>
            </div>

            <div class="fm-field fm-full">
                <label class="form-label" for="confirmPassword">
                    Confirm New Password
                </label>
                <input required name="password_confirmation" placeholder="Confirm new password" id="confirmPassword" class="form-control form-control-lg" type="password">
                <small class="text-muted form-text">Minimum 8 Characters</small>
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
                Cancel
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
