<form class="ajax-form" method="POST" action="{{ route('admin.salary-templates.assign', $salaryTemplate->id) }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Assign to Employees</h5>
            <p>Applies "{{ $salaryTemplate->name }}" as a new Salary Structure for every employee selected below.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="salary-summary mb-3">
            <div class="salary-summary-row">
                <span>Pay Type</span>
                <strong>{{ $salaryTemplate->pay_type_label }}</strong>
            </div>
            <div class="salary-summary-row">
                <span>{{ $salaryTemplate->pay_type === 'commission' ? 'Commission Rate' : ($salaryTemplate->pay_type === 'daily' ? 'Daily Rate' : 'Basic Salary') }}</span>
                <strong>{{ number_format($salaryTemplate->basic_salary, 2) }}{{ $salaryTemplate->pay_type === 'commission' ? '%' : '' }}</strong>
            </div>
            <div class="salary-summary-row salary-gross-row">
                <span>Components</span>
                <strong>
                    @forelse($salaryTemplate->items as $item)
                        {{ $item->salaryComponent->name ?? '—' }}{{ !$loop->last ? ', ' : '' }}
                    @empty
                        None
                    @endforelse
                </strong>
            </div>
        </div>

        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employees <span class="req">*</span></label>
                <select name="employee_ids[]" class="form-select select" multiple required data-placeholder="Select employees to apply this template to">
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Search and select as many employees as you like — this template will be applied to every one selected.</small>
            </div>
            <div class="fm-field">
                <label>Effective Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="effective_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
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
            <i class="ri-information-line"></i> This creates a new salary structure for each employee — it does not remove their existing ones.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Apply Template
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Applying...
            </button>
        </div>
    </div>
</form>
