<form class="ajax-form" method="POST" action="{{ route('admin.salary-templates.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Salary Template</h5>
            <p>Build this once, then apply it to any number of employees at once.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Template Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g. Retail Staff - Standard" required>
            </div>
            <div class="fm-field">
                <label>Pay Type <span class="req">*</span></label>
                <select name="pay_type" class="form-select select salary-template-pay-type" data-minimum-results-for-search="Infinity">
                    <option value="monthly">Monthly</option>
                    <option value="daily">Daily</option>
                    <option value="commission">Commission-based</option>
                </select>
            </div>
            <div class="fm-field">
                <label class="salary-template-basic-label">Basic Salary <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control salary-template-basic-input" name="basic_salary" min="0" value="0" required>
                <small class="text-muted salary-template-basic-help"></small>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2" placeholder="Optional note about who this template is for"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <hr>

        <div class="salary-template-components-hdr">
            <h6><i class="ri-list-check-2 me-1"></i> Salary Components</h6>
        </div>

        <div class="salary-template-component-rows"></div>

        <div class="salary-summary mt-3">
            <div class="salary-summary-hdr">
                <i class="ri-calculator-line"></i> Salary Summary
            </div>

            <div class="salary-summary-row">
                <span class="salary-template-basic-summary-label">Basic Salary</span>
                <strong class="salary-template-basic-total">0.00</strong>
            </div>

            <div class="salary-summary-row earning-total-row">
                <span>Total Earnings <small>(+)</small></span>
                <strong class="salary-template-earning-total text-success">0.00</strong>
            </div>

            <div class="salary-summary-row deduction-total-row">
                <span>Total Deductions <small>(−)</small></span>
                <strong class="salary-template-deduction-total text-danger">0.00</strong>
            </div>

            <div class="salary-summary-row salary-gross-row">
                <span>Gross Salary</span>
                <strong class="salary-template-gross-total">0.00</strong>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn-nx-soft-accent salary-template-component-add">
                <i class="ri-add-line"></i> Add Component
            </button>
            <span class="fm-foot-note d-none d-md-inline">
                <i class="ri-information-line"></i> * required
            </span>
        </div>
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

    <select class="d-none salary-template-component-options">
        <option value="">Select Component</option>

        @foreach($salaryComponents as $component)
            <option
                value="{{ $component->id }}"
                data-desc="{{ $component->description }}"
                data-type="{{ $component->type }}"
                data-value="{{ $component->value ?? 0 }}"
                data-calculation-type="{{ $component->calculation_type }}"
            >
                {{ $component->name }}
            </option>
        @endforeach
    </select>
</form>
