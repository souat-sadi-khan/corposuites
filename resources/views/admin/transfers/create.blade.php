<form class="ajax-form" method="POST" action="{{ route('admin.transfers.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Transfer</h5>
            <p>Record transfer. Leave "From" blank to auto-fill.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" id="employee_id" class="form-select select" data-placeholder="Select Employee" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}" value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Current Department</label>
                <input type="text" class="form-control" name="from_department" placeholder="Current department" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>To Department</label>
                <select name="to_department" id="to_department" class="form-control select" data-placeholder="Select Department">
                    <option value="">Select Department</option>
                    @foreach ($departments as $department)
                        <option data-desc="{{ $department->description }}" value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Current Designation</label>
                <input type="text" class="form-control" name="from_designation" placeholder="Current designation" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>To Designation</label>
                <select name="to_designation" id="to_designation" class="form-control select" data-placeholder="Select Department First">
                    <option value="">Select Department First</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Transfer Date <span class="req">*</span></label>
                <input type="date" value="{{ date('Y-m-d') }}" class="form-control" name="transfer_date" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Reason</label>
                <textarea class="form-control" name="reason" rows="3"></textarea>
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
