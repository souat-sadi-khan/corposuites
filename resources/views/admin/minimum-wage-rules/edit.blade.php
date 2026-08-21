<form class="ajax-form" method="POST" action="{{ route('admin.minimum-wage-rules.update', $minimumWageRule->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Minimum Wage Rule</h5>
            <p>Update: {{ $minimumWageRule->scope_label }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Country <span class="req">*</span></label>
                <input type="text" class="form-control" name="country" value="{{ old('country', $minimumWageRule->country) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>State / Province</label>
                <input type="text" class="form-control" name="state" value="{{ old('state', $minimumWageRule->state) }}" placeholder="Leave blank to apply to the whole country" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Wage Type <span class="req">*</span></label>
                <select name="wage_type" class="form-select" required>
                    <option value="monthly" {{ $minimumWageRule->wage_type === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="daily" {{ $minimumWageRule->wage_type === 'daily' ? 'selected' : '' }}>Daily</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Minimum Wage <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="minimum_wage" value="{{ old('minimum_wage', $minimumWageRule->minimum_wage) }}" required>
            </div>
            <div class="fm-field">
                <label>Effective From <span class="req">*</span></label>
                <input type="date" class="form-control" name="effective_date" value="{{ old('effective_date', $minimumWageRule->effective_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $minimumWageRule->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $minimumWageRule->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $minimumWageRule->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> A state-specific rule overrides the country-wide one for that state only. When a law changes, add a new rule with a later Effective From date rather than editing this one.
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
