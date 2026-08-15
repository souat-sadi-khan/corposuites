<form class="ajax-form" method="POST" action="{{ route('admin.languages.store') }}" enctype="multipart/form-data">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add New Language</h5>
            <p>Create a new language for the system</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="English" required autocomplete="off">
                <div class="invalid-feedback">Please enter the language name.</div>
            </div>

            <div class="fm-field">
                <label>Native Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="native_name" placeholder="English" required autocomplete="off">
                <div class="invalid-feedback">Please enter the native name.</div>
            </div>

            <div class="fm-field">
                <label>Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" placeholder="en" required autocomplete="off">
                <small class="text-muted">ISO 639-1 code (e.g., en, fr, es).</small>
                <div class="invalid-feedback">Please enter a unique language code.</div>
            </div>

            <div class="fm-field">
                <label>Direction <span class="req">*</span></label>
                <select name="direction" data- class="form-select select" data-minimum-results-for-search="Infinity" required>
                    <option selected value="ltr">Left to Right (LTR)</option>
                    <option value="rtl">Right to Left (RTL)</option>
                </select>
                <div class="invalid-feedback">Please select the text direction.</div>
            </div>
        </div>
        <div class="fm-grid">
            <div class="fm-field mt-3">
                <label>Status</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <small class="text-muted">Enable to make this language available.</small>
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
                <i class="ri-check-line me-1"></i> Save
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</form>
