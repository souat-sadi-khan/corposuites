<form class="ajax-form" method="POST" action="{{ route('admin.salary-components.update', $salaryComponent->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Salary Component</h5>
            <p>Update salary component: {{ $salaryComponent->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $salaryComponent->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" value="{{ old('code', $salaryComponent->code) }}" required>
            </div>
            <div class="fm-field">
                <label>Type <span class="req">*</span></label>
                <select name="type" class="form-select select" data-minimum-results-for-search="Infinity" required>
                    <option value="earning" {{ old('type', $salaryComponent->type) == 'earning' ? 'selected' : '' }}>Earning</option>
                    <option value="deduction" {{ old('type', $salaryComponent->type) == 'deduction' ? 'selected' : '' }}>Deduction</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Calculation Type <span class="req">*</span></label>
                <select name="calculation_type" class="form-select select salary-component-calc-type" data-minimum-results-for-search="Infinity" required>
                    <option value="fixed" {{ old('calculation_type', $salaryComponent->calculation_type) == 'fixed' ? 'selected' : '' }}>Fixed</option>
                    <option value="percentage" {{ old('calculation_type', $salaryComponent->calculation_type) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="per_occurrence" {{ old('calculation_type', $salaryComponent->calculation_type) == 'per_occurrence' ? 'selected' : '' }}>Per Occurrence</option>
                </select>
            </div>
            <div class="fm-field">
                <label class="salary-component-value-label">Value <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control" name="value" min="0" value="{{ old('value', $salaryComponent->value) }}" required>
                <small class="text-muted salary-component-value-help"></small>
            </div>
            <div class="fm-field">
                <label>Taxable</label>
                <select name="is_taxable" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('is_taxable', $salaryComponent->is_taxable) == '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ old('is_taxable', $salaryComponent->is_taxable) == '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $salaryComponent->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('status', $salaryComponent->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $salaryComponent->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
