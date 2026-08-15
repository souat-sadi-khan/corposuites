<form class="ajax-form" method="POST" action="{{ route('admin.asset-categories.update', $assetCategory->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Asset Category</h5>
            <p>Update: {{ $assetCategory->name }} ({{ $assetCategory->code }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Category Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $assetCategory->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" value="{{ old('code', $assetCategory->code) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Depreciation Method <span class="req">*</span></label>
                <select name="depreciation_method" class="form-select asset-category-depreciation" required>
                    <option value="straight_line" {{ $assetCategory->depreciation_method === 'straight_line' ? 'selected' : '' }}>Straight Line</option>
                    <option value="reducing_balance" {{ $assetCategory->depreciation_method === 'reducing_balance' ? 'selected' : '' }}>Reducing Balance</option>
                    <option value="none" {{ $assetCategory->depreciation_method === 'none' ? 'selected' : '' }}>None — does not depreciate</option>
                </select>
            </div>
            <div class="fm-field asset-category-life">
                <label>Useful Life (Years) <span class="req">*</span></label>
                <input type="number" min="1" max="100" class="form-control" name="useful_life_years" value="{{ old('useful_life_years', $assetCategory->useful_life_years) }}">
            </div>
            <div class="fm-field">
                <label>Salvage Value (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="salvage_value_percent" value="{{ old('salvage_value_percent', $assetCategory->salvage_value_percent) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $assetCategory->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $assetCategory->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $assetCategory->description) }}</textarea>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
