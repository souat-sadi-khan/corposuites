<form class="ajax-form" method="POST" action="{{ route('admin.project-timesheets.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Generate Timesheet</h5>
            <p>Pulls together an employee's finished time entries for one week</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Any Date in the Week <span class="req">*</span></label>
                <input type="date" class="form-control" name="week_start_date" value="{{ now()->toDateString() }}" required>
                <small class="text-muted">The week runs Monday to Sunday — pick any day inside it</small>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Only finished entries (a typed duration, or a stopped timer) are linked — a still-running timer is skipped until it's stopped. If a timesheet already exists for this employee and week, this re-pulls their latest entries instead of creating a duplicate.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Generate
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Generating...
            </button>
        </div>
    </div>
</form>
