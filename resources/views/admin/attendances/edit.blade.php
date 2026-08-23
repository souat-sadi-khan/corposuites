<form class="ajax-form" method="POST" action="{{ route('admin.attendances.update', $attendance->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Attendance</h5>
            <p>Update attendance record</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" data-placeholder="Select Employee" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $attendance->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="attendance_date" value="{{ old('attendance_date', $attendance->attendance_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Attendance Status <span class="req">*</span></label>
                <select name="attendance_status" class="form-select select" required data-minimum-results-for-search="Infinity">
                    <option value="present" {{ old('attendance_status', $attendance->attendance_status) == 'present' ? 'selected' : '' }}>Present</option>
                    <option value="absent" {{ old('attendance_status', $attendance->attendance_status) == 'absent' ? 'selected' : '' }}>Absent</option>
                    <option value="half_day" {{ old('attendance_status', $attendance->attendance_status) == 'half_day' ? 'selected' : '' }}>Half Day</option>
                    <option value="on_leave" {{ old('attendance_status', $attendance->attendance_status) == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="late" {{ old('attendance_status', $attendance->attendance_status) == 'late' ? 'selected' : '' }}>Late</option>
                    <option value="early_leave" {{ old('attendance_status', $attendance->attendance_status) == 'early_leave' ? 'selected' : '' }}>Early Leave</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Check In</label>
                <input type="time" class="form-control" name="check_in" value="{{ old('check_in', $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '') }}">
            </div>
            <div class="fm-field">
                <label>Check Out</label>
                <input type="time" class="form-control" name="check_out" value="{{ old('check_out', $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '') }}">
            </div>
            <div class="fm-field">
                <label>Overtime Hours</label>
                <input type="number" step="0.25" min="0" max="24" class="form-control" name="overtime_hours" value="{{ old('overtime_hours', $attendance->overtime_hours) }}">
                <small class="text-muted">Hours worked beyond the shift on this day.</small>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('status', $attendance->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $attendance->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Remarks</label>
                <textarea class="form-control" name="remarks" rows="3">{{ old('remarks', $attendance->remarks) }}</textarea>
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
