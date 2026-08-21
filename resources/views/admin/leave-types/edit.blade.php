<form class="ajax-form" method="POST" action="{{ route('admin.leave-types.update', $leaveType->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Leave Type</h5>
            <p>Update leave type: {{ $leaveType->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $leaveType->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Days Allowed <span class="req">*</span></label>
                <input type="number" class="form-control" name="days_allowed" min="0" max="365" value="{{ old('days_allowed', $leaveType->days_allowed) }}" required>
            </div>
            <div class="fm-field">
                <label>Type</label>
                <select name="is_paid" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('is_paid', $leaveType->is_paid) == '1' ? 'selected' : '' }}>Paid</option>
                    <option value="0" {{ old('is_paid', $leaveType->is_paid) == '0' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $leaveType->description) }}</textarea>
            </div>

            @php
                $selEmpTypes = old('applicable_employee_type_ids', $leaveType->applicable_employee_type_ids ?? []);
                $selDesigs = old('applicable_designation_ids', $leaveType->applicable_designation_ids ?? []);
                $accrual = old('accrual_method', $leaveType->accrual_method);
                $gender = old('applicable_gender', $leaveType->applicable_gender);
            @endphp

            <div class="fm-field">
                <label>Accrual Method</label>
                <select name="accrual_method" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="annual" {{ $accrual == 'annual' ? 'selected' : '' }}>Annual (full entitlement at year start)</option>
                    <option value="monthly" {{ $accrual == 'monthly' ? 'selected' : '' }}>Monthly (accrue each month)</option>
                    <option value="none" {{ $accrual == 'none' ? 'selected' : '' }}>None (manual allocation)</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Minimum Service (days)</label>
                <input type="number" class="form-control" name="min_service_days" min="0" max="3650" value="{{ old('min_service_days', $leaveType->min_service_days) }}">
            </div>

            <div class="fm-field">
                <label>Allow Carry Forward</label>
                <select name="allow_carry_forward" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" {{ old('allow_carry_forward', $leaveType->allow_carry_forward) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('allow_carry_forward', $leaveType->allow_carry_forward) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Max Carry Forward (days)</label>
                <input type="number" step="0.5" class="form-control" name="max_carry_forward" min="0" max="365" value="{{ old('max_carry_forward', $leaveType->max_carry_forward) }}" placeholder="e.g., 10">
            </div>
            <div class="fm-field">
                <label>Carry Forward Expiry (months)</label>
                <input type="number" class="form-control" name="carry_forward_expiry_months" min="0" max="24" value="{{ old('carry_forward_expiry_months', $leaveType->carry_forward_expiry_months) }}" placeholder="e.g., 3">
            </div>

            <div class="fm-field">
                <label>Applicable Gender</label>
                <select name="applicable_gender" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="all" {{ $gender == 'all' ? 'selected' : '' }}>All</option>
                    <option value="male" {{ $gender == 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ $gender == 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ $gender == 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Applicable Employee Types</label>
                <select name="applicable_employee_type_ids[]" class="form-select select" multiple data-placeholder="All employee types">
                    @foreach($employeeTypes as $type)
                        <option value="{{ $type->id }}" {{ in_array($type->id, (array) $selEmpTypes) ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Applicable Designations</label>
                <select name="applicable_designation_ids[]" class="form-select select" multiple data-placeholder="All designations">
                    @foreach($designations as $designation)
                        <option value="{{ $designation->id }}" {{ in_array($designation->id, (array) $selDesigs) ? 'selected' : '' }}>{{ $designation->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fm-field">
                <label>Minimum Notice (days)</label>
                <input type="number" class="form-control" name="min_notice_days" min="0" max="365" value="{{ old('min_notice_days', $leaveType->min_notice_days) }}">
            </div>
            <div class="fm-field">
                <label>Max Consecutive Days</label>
                <input type="number" class="form-control" name="max_consecutive_days" min="1" max="365" value="{{ old('max_consecutive_days', $leaveType->max_consecutive_days) }}" placeholder="No limit">
            </div>
            <div class="fm-field">
                <label>Allow Half Day</label>
                <select name="allow_half_day" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" {{ old('allow_half_day', $leaveType->allow_half_day) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('allow_half_day', $leaveType->allow_half_day) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Requires Attachment</label>
                <select name="requires_attachment" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" {{ old('requires_attachment', $leaveType->requires_attachment) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('requires_attachment', $leaveType->requires_attachment) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Encashable</label>
                <select name="is_encashable" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" {{ old('is_encashable', $leaveType->is_encashable) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_encashable', $leaveType->is_encashable) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>

            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('status', $leaveType->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $leaveType->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
