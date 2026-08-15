<form class="ajax-form" method="POST" action="{{ route('admin.salary-structures.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Salary Structure</h5>
            <p>Gross salary is calculated automatically from basic salary and components.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Effective Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="effective_date" required>
            </div>
            <div class="fm-field">
                <label>Basic Salary <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control" name="basic_salary" min="0" value="0" required>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Salary Components</label>
            <button type="button" class="btn-nx-outline btn-sm salary-component-add">
                <i class="ri-add-line"></i> Add Component
            </button>
        </div>
        <div class="salary-component-rows"></div>
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

    <select class="d-none salary-component-options">
        <option value="">Select Component</option>
        @foreach($salaryComponents as $component)
            <option value="{{ $component->id }}">{{ $component->name }} ({{ ucfirst($component->type) }})</option>
        @endforeach
    </select>
</form>
