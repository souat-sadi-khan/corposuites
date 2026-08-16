<form class="ajax-form" method="POST" action="{{ route('admin.promotions.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Promotion</h5>
            <p>Record a designation/salary promotion.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" id="employee_id" class="form-select select" required data-placeholder="Select Employee">
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->department ? $employee->department->name : 'Unassigned Department' }}" value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>From Designation</label>
                <input type="text" class="form-control" name="from_designation" placeholder="Current designation" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>To Designation <span class="req">*</span></label>
                <select name="to_designation" id="to_designation" class="form-control select" required data-placeholder="Select Designation">
                    <option value="">Select Designation</option>
                </select>
            </div>
            <div class="fm-field">
                <label>From Salary</label>
                <input type="number" step="0.01" class="form-control" name="from_salary" min="0">
            </div>
            <div class="fm-field">
                <label>To Salary</label>
                <input type="number" step="0.01" class="form-control" name="to_salary" min="0">
            </div>
            <div class="fm-field">
                <label>Promotion Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="promotion_date" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Remarks</label>
                <textarea class="form-control" name="remarks" rows="3"></textarea>
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
