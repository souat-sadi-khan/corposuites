<form class="ajax-form" method="POST" action="{{ route('admin.bank-accounts.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Bank Account</h5>
            <p>Add a new bank account for an employee</p>
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
                <label>Bank Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="bank_name" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Account Holder Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="account_holder_name" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Account Number <span class="req">*</span></label>
                <input type="text" class="form-control" name="account_number" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>IFSC / SWIFT Code</label>
                <input type="text" class="form-control" name="ifsc_swift_code" autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>Branch Name</label>
                <input type="text" class="form-control" name="branch_name" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Primary Account</label>
                <select name="is_primary" class="form-select">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="fm-field">
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
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
