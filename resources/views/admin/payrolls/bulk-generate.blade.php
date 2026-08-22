<form class="ajax-form" method="POST" action="{{ route('admin.payrolls.bulk-generate') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Generate Payroll for All Employees</h5>
            <p>Runs payroll for every active employee matching the filters below — <br> leave all filters blank to include everyone.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Month <span class="req">*</span></label>
                <select name="month" class="form-select select" data-minimum-results-for-search="Infinity" required>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (int) date('n') == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Year <span class="req">*</span></label>
                <input type="number" class="form-control" name="year" min="1950" max="2100" value="{{ date('Y') }}" required>
            </div>

            <div class="adv-search-section"><i class="ri-filter-3-line"></i> Filter Employees (optional)</div>

            <div class="fm-field">
                <label>Department</label>
                <select name="department_id" class="form-select select" data-placeholder="All Departments">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option data-desc="{{ $department->description }}" value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Designation</label>
                <select name="designation_id" class="form-select select" data-placeholder="All Designations">
                    <option value="">All Designations</option>
                    @foreach($designations as $designation)
                        <option data-desc="{{ $designation->description }}" value="{{ $designation->id }}">{{ $designation->name }}{{ $designation->department ? ' — '.$designation->department->name : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Shift</label>
                <select name="shift_id" class="form-select select" data-placeholder="All Shifts">
                    <option value="">All Shifts</option>
                    @foreach($shifts as $shift)
                        <option data-desc="{{ $shift->description }}" value="{{ $shift->id }}">{{ $shift->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Employment Status</label>
                <select name="employment_status_id" class="form-select select" data-placeholder="All Employment Statuses">
                    <option value="">All Employment Statuses</option>
                    @foreach($employmentStatuses as $item)
                        <option data-desc="{{ $item->description }}" value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Employee Type</label>
                <select name="employee_type_id" class="form-select select" data-placeholder="All Employee Types">
                    <option value="">All Employee Types</option>
                    @foreach($employeeTypes as $item)
                        <option data-desc="{{ $item->description }}" value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Gender</label>
                <select name="gender" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="fm-field fm-full">
                <i class="ri-information-line"></i> Employees who already have a payroll for this period, have no active salary structure, are commission-based, or have per-occurrence components are skipped automatically — check Activity Logs for who and why.
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> <span class="text-danger">*</span> fields are required.
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
