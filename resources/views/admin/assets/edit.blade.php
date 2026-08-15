<form class="ajax-form" method="POST" action="{{ route('admin.assets.update', $asset->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Asset</h5>
            <p>Update: {{ $asset->name }} ({{ $asset->asset_code }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Asset Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $asset->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Asset Category <span class="req">*</span></label>
                <select name="asset_category_id" class="form-select select" required>
                    <option value="">Select a category</option>
                    @foreach($assetCategories as $category)
                        <option value="{{ $category->id }}" {{ (int) $asset->asset_category_id === $category->id ? 'selected' : '' }}>{{ $category->name }} ({{ $category->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Serial Number</label>
                <input type="text" class="form-control" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}">
            </div>
            <div class="fm-field">
                <label>Model Number</label>
                <input type="text" class="form-control" name="model_number" value="{{ old('model_number', $asset->model_number) }}">
            </div>
            <div class="fm-field">
                <label>Manufacturer</label>
                <input type="text" class="form-control" name="manufacturer" value="{{ old('manufacturer', $asset->manufacturer) }}">
            </div>
            <div class="fm-field">
                <label>Condition <span class="req">*</span></label>
                <select name="condition" class="form-select" required>
                    @foreach(['new' => 'New', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $value => $label)
                        <option value="{{ $value }}" {{ $asset->condition === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Asset State <span class="req">*</span></label>
                <select name="asset_status" class="form-select" required>
                    @foreach(['in_store' => 'In Store', 'in_use' => 'In Use', 'under_maintenance' => 'Under Maintenance', 'disposed' => 'Disposed'] as $value => $label)
                        <option value="{{ $value }}" {{ $asset->asset_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $asset->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $asset->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $asset->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $asset->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The asset tag {{ $asset->asset_code }} is fixed and cannot be changed.
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
