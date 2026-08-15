<form class="ajax-form" method="POST" action="{{ route('admin.leave-requests.update', $leaveRequest->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Leave Request</h5>
            <p>Update leave request record</p>
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
                        <option value="{{ $employee->id }}" {{ old('employee_id', $leaveRequest->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Leave Type <span class="req">*</span></label>
                <select name="leave_type_id" class="form-select select" required>
                    <option value="">Select Leave Type</option>
                    @foreach($leaveTypes as $leaveType)
                        <option value="{{ $leaveType->id }}" {{ old('leave_type_id', $leaveRequest->leave_type_id) == $leaveType->id ? 'selected' : '' }}>{{ $leaveType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Start Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', $leaveRequest->start_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>End Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="end_date" value="{{ old('end_date', $leaveRequest->end_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $leaveRequest->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $leaveRequest->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Reason</label>
                <textarea class="form-control" name="reason" rows="3">{{ old('reason', $leaveRequest->reason) }}</textarea>
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
