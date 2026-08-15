<form class="ajax-form" method="POST" action="{{ route('admin.unit-conversions.update', $unitConversion->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Unit Conversion</h5>
            <p>Update this unit conversion</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>From Unit <span class="req">*</span></label>
                <select name="from_unit_id" class="form-select select" required>
                    <option value="">Select Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('from_unit_id', $unitConversion->from_unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->short_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>To Unit <span class="req">*</span></label>
                <select name="to_unit_id" class="form-select select" required>
                    <option value="">Select Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('to_unit_id', $unitConversion->to_unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->short_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Conversion Factor <span class="req">*</span></label>
                <input type="number" step="0.000001" min="0" class="form-control" name="conversion_factor" value="{{ old('conversion_factor', $unitConversion->conversion_factor) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $unitConversion->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $unitConversion->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $unitConversion->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
