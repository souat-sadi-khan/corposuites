<form class="ajax-form" method="POST" action="{{ route('admin.salary-components.bulk-assign', $salaryComponent->id) }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Bulk Assign to Employees</h5>
            <p>Adds or updates "{{ $salaryComponent->name }}" on each selected employee's own active salary structure — their other components are left untouched.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    @php
        $calcSummary = match ($salaryComponent->calculation_type) {
            'percentage' => number_format($salaryComponent->value, 2) . '% of basic salary',
            'per_occurrence' => format_currency($salaryComponent->value) . ' per occurrence',
            default => format_currency($salaryComponent->value) . ' fixed',
        };
    @endphp

    <div class="modal-body fm-modal-body fm-body">
        <div class="salary-summary mb-3">
            <div class="salary-summary-row">
                <span>Type</span>
                <strong>{{ $salaryComponent->type === 'earning' ? 'Earning' : 'Deduction' }}</strong>
            </div>
            <div class="salary-summary-row">
                <span>Calculation</span>
                <strong>{{ $calcSummary }}</strong>
            </div>
        </div>

        @if($salaryComponent->calculation_type === 'per_occurrence')
            <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
                <i class="ri-information-line mt-1"></i>
                <span>This is a per-occurrence component — the rate assigned here is applied per event, but the actual occurrence count (e.g. how many late days) is entered separately each time payroll is generated for these employees.</span>
            </div>
        @endif

        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employees <span class="req">*</span></label>
                <select name="employee_ids[]" class="form-select select" multiple required data-placeholder="Select employees to assign this component to">
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Only employees with an active salary structure will be updated — others are skipped and reported back to you.</small>
            </div>
            <div class="fm-field fm-full">
                <label>Amount Override</label>
                <input type="number" step="0.01" min="0" class="form-control" name="amount" placeholder="Leave blank to auto-calculate">
                <small class="text-muted">
                    Leave blank to let each employee get this component's own rule applied to their own basic salary ({{ $calcSummary }}).
                    Enter a value here instead to give every selected employee the exact same flat rate.
                </small>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> This updates existing salary structures — it does not create new ones.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Assign
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Assigning...
            </button>
        </div>
    </div>
</form>
