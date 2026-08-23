<form class="ajax-form" method="POST" action="{{ route('admin.attendance-adjustments.update', $attendanceAdjustment->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Attendance Adjustment</h5>
            <p>Update adjustment request</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" data-placeholder="Select Employee" class="form-select select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}" value="{{ $employee->id }}" {{ old('employee_id', $attendanceAdjustment->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="adjustment_date" value="{{ old('adjustment_date', $attendanceAdjustment->adjustment_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $attendanceAdjustment->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $attendanceAdjustment->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Requested Check In</label>
                <input type="time" class="form-control" name="requested_check_in" value="{{ old('requested_check_in', $attendanceAdjustment->requested_check_in ? \Carbon\Carbon::parse($attendanceAdjustment->requested_check_in)->format('H:i') : '') }}">
            </div>
            <div class="fm-field">
                <label>Requested Check Out</label>
                <input type="time" class="form-control" name="requested_check_out" value="{{ old('requested_check_out', $attendanceAdjustment->requested_check_out ? \Carbon\Carbon::parse($attendanceAdjustment->requested_check_out)->format('H:i') : '') }}">
            </div>
            <div class="fm-field fm-full">
                <label>Reason <span class="req">*</span></label>
                <textarea class="form-control" name="reason" rows="3" required>{{ old('reason', $attendanceAdjustment->reason) }}</textarea>
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
