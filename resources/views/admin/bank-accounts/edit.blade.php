<form class="ajax-form" method="POST" action="{{ route('admin.bank-accounts.update', $bankAccount->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Bank Account</h5>
            <p>Update account: {{ $bankAccount->bank_name }}</p>
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
                        <option value="{{ $employee->id }}" {{ old('employee_id', $bankAccount->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Bank Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $bankAccount->bank_name) }}" required>
            </div>
            <div class="fm-field">
                <label>Account Holder Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="account_holder_name" value="{{ old('account_holder_name', $bankAccount->account_holder_name) }}" required>
            </div>
            <div class="fm-field">
                <label>Account Number <span class="req">*</span></label>
                <input type="text" class="form-control" name="account_number" value="{{ old('account_number', $bankAccount->account_number) }}" required>
            </div>
            <div class="fm-field">
                <label>IFSC / SWIFT Code</label>
                <input type="text" class="form-control" name="ifsc_swift_code" value="{{ old('ifsc_swift_code', $bankAccount->ifsc_swift_code) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Branch Name</label>
                <input type="text" class="form-control" name="branch_name" value="{{ old('branch_name', $bankAccount->branch_name) }}">
            </div>
            <div class="fm-field">
                <label>Primary Account</label>
                <select name="is_primary" class="form-select">
                    <option value="0" {{ old('is_primary', $bankAccount->is_primary) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_primary', $bankAccount->is_primary) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $bankAccount->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $bankAccount->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
