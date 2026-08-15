<form class="ajax-form" method="POST" action="{{ route('admin.assets.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Register Asset</h5>
            <p>Add a physical asset to the register — an asset tag is issued automatically</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Asset Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Dell Latitude 5540" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Asset Category <span class="req">*</span></label>
                <select name="asset_category_id" class="form-select select" required>
                    <option value="">Select a category</option>
                    @foreach($assetCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Serial Number</label>
                <input type="text" class="form-control" name="serial_number" placeholder="Optional, must be unique">
            </div>
            <div class="fm-field">
                <label>Model Number</label>
                <input type="text" class="form-control" name="model_number" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Manufacturer</label>
                <input type="text" class="form-control" name="manufacturer" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Condition <span class="req">*</span></label>
                <select name="condition" class="form-select" required>
                    <option value="new">New</option>
                    <option value="good" selected>Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Asset State <span class="req">*</span></label>
                <select name="asset_status" class="form-select" required>
                    <option value="in_store">In Store</option>
                    <option value="in_use">In Use</option>
                    <option value="under_maintenance">Under Maintenance</option>
                    <option value="disposed">Disposed</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Purchase details, assignment and location are recorded in their own modules once this asset exists.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Register
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
