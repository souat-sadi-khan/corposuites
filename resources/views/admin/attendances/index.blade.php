@extends('admin.layout.app', ['title' => 'Attendance', 'modal' => 'lg'])

@section('content')
    @if(request('employee_id'))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span><i class="ri-filter-3-line me-1"></i> Showing attendance records for the selected employee.</span>
            <a href="{{ route('admin.attendances.index') }}" class="btn-nx-outline btn-sm">Clear Filter</a>
        </div>
    @endif

    <div class="tl-toolbar">
        <a href="{{ route('admin.attendances.monthly') }}" class="btn-nx-outline"><i class="ri-calendar-2-line"></i> Monthly Report</a>
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="attendanceSearch" placeholder="Search Attendance">
        </div>

        <button type="button" class="btn-nx-outline att-adv-toggle" id="attAdvSearchToggle">
            <i class="ri-equalizer-2-line"></i> Advanced Search
            <span class="att-adv-count d-none" id="attAdvCount">0</span>
        </button>

        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="tlFilterBtn" title="Filter">
                <i class="ri-equalizer-line"></i>
            </button>

            <div class="tl-filter-dd" id="tlFilterDd">
                <div class="tl-filter-dd-title">
                    Filter by Status
                </div>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="1" checked>
                    Active
                </label>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="0" checked>
                    Inactive
                </label>
            </div>
        </div>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        @if(Auth::guard('admin')->user()?->can('attendance.create'))
        <button id="openModal" data-url="{{ route('admin.attendances.create', request('employee_id') ? ['employee_id' => request('employee_id')] : []) }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Attendance
        </button>
        @endif
    </div>

    {{--
        Advanced Search panel — collapsed by default, toggled by the button
        above. Every field posts straight into the existing server-side
        DataTable's own ajax `data` callback (attendances.js) and redraws —
        this is a live filter on the SAME list, not a page-reloading GET form
        the way the whole-page Attendance Report/Monthly Sheet screens use,
        since this screen is a DataTables AJAX list. Department/Designation/
        Shift/Employee Type/Employment Status option lists are the exact
        same ones (AttendanceReportService::filterOptions()) those two other
        screens already use, so the choices are always identical everywhere.
    --}}
    <div class="nx-card att-adv-panel d-none" id="attAdvPanel">
        <div class="row g-2">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Date From</label>
                <input type="date" id="attAdvDateFrom" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Date To</label>
                <input type="date" id="attAdvDateTo" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Department</label>
                <select id="attAdvDepartment" class="form-select select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($filters['departments'] as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Designation</label>
                <select id="attAdvDesignation" class="form-select select form-select-sm">
                    <option value="">All Designations</option>
                    @foreach($filters['designations'] as $designation)
                        <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Shift</label>
                <select id="attAdvShift" class="form-select select form-select-sm">
                    <option value="">All Shifts</option>
                    @foreach($filters['shifts'] as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Employee Type</label>
                <select id="attAdvEmployeeType" class="form-select select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($filters['employeeTypes'] as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Employment Status</label>
                <select id="attAdvEmploymentStatus" class="form-select select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach($filters['employmentStatuses'] as $es)
                        <option value="{{ $es->id }}">{{ $es->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Employee</label>
                <select id="attAdvEmployee" class="form-select select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($filters['allEmployeesForFilter'] as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1 d-block">Attendance Status</label>
                <div class="att-adv-status-chks">
                    @foreach(['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'half_day' => 'Half Day', 'early_leave' => 'Early Leave', 'on_leave' => 'On Leave'] as $val => $label)
                        <label class="att-adv-chip"><input type="checkbox" class="att-adv-status-chk" value="{{ $val }}"> {{ $label }}</label>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-md-3 d-flex align-items-end">
                <label class="att-adv-chip att-adv-chip-block">
                    <input type="checkbox" id="attAdvMissingCheckout"> Missing Checkout Only
                </label>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 mt-1">
                <button type="button" class="btn-nx-outline btn-sm" id="attAdvReset"><i class="ri-refresh-line"></i> Reset</button>
                <button type="button" class="btn-nx-primary btn-sm" id="attAdvApply"><i class="ri-search-line"></i> Apply Filters</button>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="attendanceTable" data-url="{{ route('admin.attendances.index') }}" data-employee-id="{{ request('employee_id') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Timing</th>
                        <th>Location</th>
                        <th>Attendance</th>
                        <th>Adjustment</th>
                        <th>Status</th>
                        <th class="no-sort text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="tl-footer">
            <div class="tl-info" id="tlInfo"></div>
            <div class="tl-pagination">
                <button class="tl-page-btn" id="tlPrev" title="Previous page">
                    <i class="ri-arrow-left-s-line"></i>
                </button>
                <button class="tl-page-btn" id="tlNext" title="Next page">
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/attendances.js') }}"></script>
    <script>_componentSelect();</script>
@endpush
