<form class="ajax-form" method="POST" action="{{ route('admin.employees.store-login', $employee->id) }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Create Login</h5>
            <p>Create a login account for {{ $employee->full_name }} ({{ $employee->employee_code }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Role <span class="req">*</span></label>
                <select name="role_id" class="form-select select" data-placeholder="Select Role" required>
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option data-desc="{{ $role->notes }}" value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fm-field fm-full">
                <label>Email <span class="req">*</span></label>
                <input type="email" class="form-control" name="email" value="{{ $employee->email }}" required autocomplete="off">
            </div>
            
            <div class="fm-field">
                <label>Password <span class="req">*</span></label>
                <input type="password" class="form-control" name="password" minlength="8" required autocomplete="new-password">
            </div>

            <div class="fm-field">
                <label>Confirm Password <span class="req">*</span></label>
                <input type="password" class="form-control" name="password_confirmation" minlength="8" required autocomplete="new-password">
            </div>
            
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> This creates an admin login account <br> linked to this employee.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Create Login
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
