<form class="ajax-form" method="POST" action="{{ route('admin.payrolls.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Generate Payroll</h5>
            <p>Salary is pulled automatically from the employee's active salary structure.</p>
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
                <label>Month <span class="req">*</span></label>
                <select name="month" class="form-select" required>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (int)date('n') == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Year <span class="req">*</span></label>
                <input type="number" class="form-control" name="year" min="1950" max="2100" value="{{ date('Y') }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
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
                <i class="ri-check-line me-1"></i> Generate
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Generating...
            </button>
        </div>
    </div>
</form>
