<form class="ajax-form" method="POST" action="{{ route('admin.asset-categories.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Asset Category</h5>
            <p>Classify assets and set their default depreciation behaviour</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Category Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Office Equipment, Vehicles" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" placeholder="e.g., OFFEQ, VEH" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Depreciation Method <span class="req">*</span></label>
                <select name="depreciation_method" class="form-select asset-category-depreciation" required>
                    <option value="straight_line">Straight Line</option>
                    <option value="reducing_balance">Reducing Balance</option>
                    <option value="none">None — does not depreciate</option>
                </select>
            </div>
            <div class="fm-field asset-category-life">
                <label>Useful Life (Years) <span class="req">*</span></label>
                <input type="number" min="1" max="100" class="form-control" name="useful_life_years" placeholder="e.g., 5">
            </div>
            <div class="fm-field">
                <label>Salvage Value (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="salvage_value_percent" value="0">
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
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> These values act as defaults for assets in this category — the Depreciation Calculation module will read them.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
