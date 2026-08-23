<form class="ajax-form" method="POST" action="{{ route('admin.attendance-adjustments.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Attendance Adjustment</h5>
            <p>Request a correction to an employee's attendance</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        @if($pendingExists ?? false)
            <div class="alert alert-warning py-2 px-3 small mb-3">
                <i class="ri-time-line"></i> This employee already has a <strong>pending</strong> adjustment request for this date — review it in the list instead of creating another.
            </div>
        @endif
        @if($existingAttendance ?? null)
            <div class="alert alert-secondary py-2 px-3 small mb-3">
                <i class="ri-information-line"></i> Currently recorded for this day:
                <strong>{{ $existingAttendance->check_in ? \Carbon\Carbon::parse($existingAttendance->check_in)->format('h:i A') : '--' }}</strong> in ·
                <strong>{{ $existingAttendance->check_out ? \Carbon\Carbon::parse($existingAttendance->check_out)->format('h:i A') : '--' }}</strong> out ·
                {{ ucwords(str_replace('_', ' ', $existingAttendance->attendance_status)) }}
            </div>
        @endif
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" data-placeholder="Select Employee" class="form-select select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}" value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="adjustment_date" value="{{ isset($prefillDate) && $prefillDate ? $prefillDate->toDateString() : '' }}" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Requested Check In</label>
                <input type="time" class="form-control" name="requested_check_in" value="{{ ($existingAttendance->check_in ?? null) ? \Carbon\Carbon::parse($existingAttendance->check_in)->format('H:i') : '' }}">
            </div>
            <div class="fm-field">
                <label>Requested Check Out</label>
                <input type="time" class="form-control" name="requested_check_out" value="{{ ($existingAttendance->check_out ?? null) ? \Carbon\Carbon::parse($existingAttendance->check_out)->format('H:i') : '' }}">
            </div>
            <div class="fm-field fm-full">
                <label>Reason <span class="req">*</span></label>
                <textarea class="form-control" name="reason" rows="3" required></textarea>
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
