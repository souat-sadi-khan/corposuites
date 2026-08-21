<form class="ajax-form" method="POST" action="{{ route('admin.salary-components.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Salary Component</h5>
            <p>Create a new salary component</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., House Rent Allowance" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" placeholder="e.g., HRA" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Type <span class="req">*</span></label>
                <select name="type" class="form-select select" data-minimum-results-for-search="Infinity" required>
                    <option value="earning">Earning</option>
                    <option value="deduction">Deduction</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Calculation Type <span class="req">*</span></label>
                <select name="calculation_type" class="form-select select salary-component-calc-type" data-minimum-results-for-search="Infinity" required>
                    <option value="fixed">Fixed</option>
                    <option value="percentage">Percentage</option>
                    <option value="per_occurrence">Per Occurrence</option>
                </select>
            </div>
            <div class="fm-field">
                <label class="salary-component-value-label">Value <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control" name="value" min="0" value="0" required>
                <small class="text-muted salary-component-value-help"></small>
            </div>
            <div class="fm-field">
                <label>Taxable</label>
                <select name="is_taxable" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Yes</option>
                    <option selected value="0">No</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the component"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
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
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
