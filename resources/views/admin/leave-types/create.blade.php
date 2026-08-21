<form class="ajax-form" method="POST" action="{{ route('admin.leave-types.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Leave Type</h5>
            <p>Create a new leave type</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Annual Leave" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Days Allowed <span class="req">*</span></label>
                <input type="number" class="form-control" name="days_allowed" min="0" max="365" value="0" required>
            </div>
            <div class="fm-field">
                <label>Type</label>
                <select name="is_paid" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Paid</option>
                    <option value="0">Unpaid</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the leave type"></textarea>
            </div>

            <div class="fm-field">
                <label>Accrual Method</label>
                <select name="accrual_method" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="annual" selected>Annual (full entitlement at year start)</option>
                    <option value="monthly">Monthly (accrue each month)</option>
                    <option value="none">None (manual allocation)</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Minimum Service (days)</label>
                <input type="number" class="form-control" name="min_service_days" min="0" max="3650" value="0">
            </div>

            <div class="fm-field">
                <label>Allow Carry Forward</label>
                <select name="allow_carry_forward" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Max Carry Forward (days)</label>
                <input type="number" step="0.5" class="form-control" name="max_carry_forward" min="0" max="365" value="{{ get_settings('hrm_leave_default_carry_forward_days') }}" placeholder="e.g., 10">
            </div>
            <div class="fm-field">
                <label>Carry Forward Expiry (months)</label>
                <input type="number" class="form-control" name="carry_forward_expiry_months" min="0" max="24" placeholder="e.g., 3">
            </div>

            <div class="fm-field">
                <label>Applicable Gender</label>
                <select name="applicable_gender" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="all" selected>All</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Applicable Employee Types</label>
                <select name="applicable_employee_type_ids[]" class="form-select select" multiple data-placeholder="All employee types">
                    @foreach($employeeTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Applicable Designations</label>
                <select name="applicable_designation_ids[]" class="form-select select" multiple data-placeholder="All designations">
                    @foreach($designations as $designation)
                        <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fm-field">
                <label>Minimum Notice (days)</label>
                <input type="number" class="form-control" name="min_notice_days" min="0" max="365" value="0">
            </div>
            <div class="fm-field">
                <label>Max Consecutive Days</label>
                <input type="number" class="form-control" name="max_consecutive_days" min="1" max="365" placeholder="No limit">
            </div>
            <div class="fm-field">
                <label>Allow Half Day</label>
                <select name="allow_half_day" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Requires Attachment</label>
                <select name="requires_attachment" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Encashable</label>
                <select name="is_encashable" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                </select>
            </div>

            <div class="fm-field fm-full">
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
