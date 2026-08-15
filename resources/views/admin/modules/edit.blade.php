<form class="ajax-form" method="POST" action="{{ route('admin.modules.update', $module->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Module</h5>
            <p>Update module: {{ $module->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $module->name) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Slug</label>
                <input type="text" class="form-control" name="slug" value="{{ old('slug', $module->slug) }}">
                <small class="text-muted">Leave blank to auto-generate from name.</small>
            </div>
            <div class="fm-field">
                <label>Version</label>
                <input type="text" class="form-control" name="version" value="{{ old('version', $module->version) }}">
            </div>
            <div class="fm-field">
                <label>Icon (Bootstrap Icons class)</label>
                <input type="text" class="form-control" name="icon" value="{{ old('icon', $module->icon) }}" placeholder="bi-box">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $module->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="0" {{ old('status', $module->status) == '0' ? 'selected' : '' }}>Inactive</option>
                    <option value="1" {{ old('status', $module->status) == '1' ? 'selected' : '' }}>Active</option>
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
