<form class="ajax-form" method="POST" action="{{ route('admin.leave-requests.update', $leaveRequest->id) }}" enctype="multipart/form-data">
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
                <select name="employee_id" class="form-select select" required data-placeholder="Select Employee">
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}" value="{{ $employee->id }}" {{ old('employee_id', $leaveRequest->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Leave Type <span class="req">*</span></label>
                <select name="leave_type_id" class="form-select select" required data-placeholder="Select Leave Type">
                    <option value="">Select Leave Type</option>
                    @foreach($leaveTypes as $leaveType)
                        <option data-desc="{{ $leaveType->description }}" value="{{ $leaveType->id }}" {{ old('leave_type_id', $leaveRequest->leave_type_id) == $leaveType->id ? 'selected' : '' }}>{{ $leaveType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Duration <span class="req">*</span></label>
                <select name="duration_type" class="form-select select" id="durationType" data-minimum-results-for-search="Infinity">
                    <option value="full_day" {{ old('duration_type', $leaveRequest->duration_type) == 'full_day' ? 'selected' : '' }}>Full Day</option>
                    <option value="half_day" {{ old('duration_type', $leaveRequest->duration_type) == 'half_day' ? 'selected' : '' }}>Half Day</option>
                </select>
            </div>
            <div class="fm-field" id="halfDaySessionWrap" style="{{ old('duration_type', $leaveRequest->duration_type) == 'half_day' ? '' : 'display:none;' }}">
                <label>Half-Day Session</label>
                <select name="half_day_session" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="first_half" {{ old('half_day_session', $leaveRequest->half_day_session) == 'first_half' ? 'selected' : '' }}>First Half</option>
                    <option value="second_half" {{ old('half_day_session', $leaveRequest->half_day_session) == 'second_half' ? 'selected' : '' }}>Second Half</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Start Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="start_date" id="startDate" value="{{ old('start_date', $leaveRequest->start_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field" id="endDateWrap">
                <label>End Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="end_date" id="endDate" value="{{ old('end_date', $leaveRequest->end_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Attachment</label>
                <input type="file" class="form-control" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                @if($leaveRequest->attachment)
                    <small class="text-muted">Current: <a href="{{ asset($leaveRequest->attachment) }}" target="_blank">view file</a>. Upload to replace.</small>
                @else
                    <small class="text-muted">Required for some leave types (e.g. medical). Max 4MB.</small>
                @endif
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
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
