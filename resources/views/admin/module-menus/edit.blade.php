<form class="ajax-form" method="POST" action="{{ route('admin.module-menus.update', $model->id) }}">
    @method('PATCH')
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Edit Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="fm-grid fm-body">
            <div class="fm-field fm-full">
                <label for="module_id" class="form-label">Module <span class="req">*</span></label>
                <select name="module_id" id="module_id" class="form-select select" data-placeholder="Select Module" required>
                    <option value="">Select Module</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod->id }}" {{ old('module_id', $model->module_id) == $mod->id ? 'selected' : '' }}>{{ $mod->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label for="parent_id" class="form-label">Parent Menu</label>
                <select name="parent_id" id="parent_id" class="form-select">
                    <option value="">None</option>
                    <!-- Will be populated via JS on module change, but we can pre-populate with existing parent -->
                </select>
            </div>
            <div class="fm-field fm-full">
                <label for="label" class="form-label">Label <span class="req">*</span></label>
                <input type="text" name="label" id="label" class="form-control" value="{{ old('label', $model->label) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label for="name" class="form-label">Name (unique identifier) <span class="req">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $model->name) }}" required>
            </div>
            <div class="fm-field">
                <label for="icon" class="form-label">Icon</label>
                <input type="text" name="icon" id="icon" class="form-control" value="{{ old('icon', $model->icon) }}" placeholder="bi-box">
            </div>
            <div class="fm-field">
                <label for="order" class="form-label">Order</label>
                <input type="number" name="order" id="order" class="form-control" value="{{ old('order', $model->order) }}" min="0">
            </div>
            <div class="fm-field fm-full">
                <label for="route" class="form-label">Route Name</label>
                <input type="text" name="route" id="route" class="form-control" value="{{ old('route', $model->route) }}" placeholder="admin.erp.products.index">
            </div>
            <div class="fm-field fm-full">
                <label for="url" class="form-label">URL (if no route)</label>
                <input type="text" name="url" id="url" class="form-control" value="{{ old('url', $model->url) }}" placeholder="/erp/products">
            </div>
            <div class="fm-field fm-full">
                <label for="permission" class="form-label">Permission (e.g., erp.products.view)</label>
                <input type="text" name="permission" id="permission" class="form-control" value="{{ old('permission', $model->permission) }}" placeholder="erp.products.view">
            </div>
            <div class="fm-field fm-full">
                <div class="form-check form-switch">
                    <input type="checkbox" name="status" id="status" class="form-check-input" value="1" {{ old('status', $model->status) ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">Active</label>
                </div>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer p-3 border-top">
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1"></span> Updating...
            </button>
        </div>
    </div>
</form>
