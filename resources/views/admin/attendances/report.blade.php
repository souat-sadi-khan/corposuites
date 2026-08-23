@extends('admin.layout.app', ['title' => 'Attendance Report'])

@section('content')

<div class="sec-hdr d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2>Attendance Report</h2>
        <div class="sec-sub">Advanced filtering and organization-wide attendance totals for the selected period</div>
    </div>

    <div class="d-flex fm-body align-items-center gap-2">
        <a href="{{ route('admin.attendances.monthly') }}" class="btn-nx-primary btn-sm"><i class="ri-calendar-todo-line"></i> Monthly Sheet</a>
        <a href="{{ route('admin.attendances.index') }}" class="btn-nx-outline btn-sm"><i class="ri-table-line"></i> Detailed List</a>
        {{-- Carries whatever filters are CURRENTLY applied on this page along
             with it (PART 11: "Do not export the unfiltered dataset when
             filters are active") — the export route re-runs the exact same
             filter pipeline this page's own query string already reflects. --}}
        <a href="{{ route('admin.attendances.report.export.pdf') }}?{{ request()->getQueryString() }}" target="_blank" class="btn-nx-outline btn-sm"><i class="ri-file-pdf-2-line"></i> Export PDF</a>
        <a href="{{ route('admin.attendances.report.export.excel') }}?{{ request()->getQueryString() }}" class="btn-nx-outline btn-sm"><i class="ri-file-excel-2-line"></i> Export Excel</a>
        <a href="{{ route('admin.attendances.report.export.csv') }}?{{ request()->getQueryString() }}" class="btn-nx-outline btn-sm"><i class="ri-file-list-3-line"></i> Export CSV</a>
    </div>
</div>

<div class="nx-card fm-body mb-3">
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Department</label>
            <select name="department_id" class="form-select select form-select-sm">
                <option value="">All Departments</option>
                @foreach($filters['departments'] as $dept)
                    <option value="{{ $dept->id }}" data-desc="{{ $dept->description }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Designation</label>
            <select name="designation_id" class="form-select select form-select-sm">
                <option value="">All Designations</option>
                @foreach($filters['designations'] as $designation)
                    <option data-desc="{{ $designation->description }}" value="{{ $designation->id }}" @selected(request('designation_id') == $designation->id)>{{ $designation->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Shift</label>
            <select name="shift_id" class="form-select select form-select-sm">
                <option value="">All Shifts</option>
                @foreach($filters['shifts'] as $shift)
                    <option data-desc="{{ $shift->description }}" value="{{ $shift->id }}" @selected(request('shift_id') == $shift->id)>{{ $shift->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Employee Type</label>
            <select name="employee_type_id" class="form-select select form-select-sm">
                <option value="">All Types</option>
                @foreach($filters['employeeTypes'] as $type)
                    <option data-desc="{{ $type->description }}" value="{{ $type->id }}" @selected(request('employee_type_id') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Employment Status</label>
            <select name="employment_status_id" class="form-select select form-select-sm">
                <option value="">All Statuses</option>
                @foreach($filters['employmentStatuses'] as $es)
                    <option data-desc="{{ $es->description }}" value="{{ $es->id }}" @selected(request('employment_status_id') == $es->id)>{{ $es->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Employee</label>
            <select name="employee_id" class="form-select select form-select-sm">
                <option value="">All Employees</option>
                @foreach($filters['allEmployeesForFilter'] as $emp)
                    <option data-logo="{{ $emp->photo ? asset($emp->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $emp->email }}" value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-12 d-flex flex-wrap gap-3 align-items-center">
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" name="late_only" value="1" id="lateOnly" @checked(request('late_only'))>
                <label class="form-check-label small" for="lateOnly">Late attendance only</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" name="missing_checkout_only" value="1" id="missingCheckoutOnly" @checked(request('missing_checkout_only'))>
                <label class="form-check-label small" for="missingCheckoutOnly">Missing checkout only</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" name="overtime_only" value="1" id="overtimeOnly" @checked(request('overtime_only'))>
                <label class="form-check-label small" for="overtimeOnly">Overtime only</label>
            </div>

            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-nx-primary btn-sm"><i class="ri-search-line"></i> Apply Filters</button>
                <a href="{{ route('admin.attendances.report') }}" class="btn btn-nx-outline btn-sm" title="Reset filters"><i class="ri-refresh-line"></i> Reset</a>
            </div>
        </div>
        <div class="col-12">
            <small class="text-muted">Fill Date From / Date To for a custom range (overrides Month, capped at 92 days), or use Month alone for a full calendar month. Filters combine together.</small>
        </div>
    </form>
</div>

<div class="stats-grid mb-3">
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Present</div><div class="stat-val">{{ number_format($totals['present']) }}</div></div><div class="stat-icon-wrap si-green"><i class="ri-checkbox-circle-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Absent</div><div class="stat-val">{{ number_format($totals['absent']) }}</div></div><div class="stat-icon-wrap si-red"><i class="ri-close-circle-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Late</div><div class="stat-val">{{ number_format($totals['late']) }}</div></div><div class="stat-icon-wrap si-amber"><i class="ri-alarm-warning-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">On Leave</div><div class="stat-val">{{ number_format($totals['on_leave']) }}</div></div><div class="stat-icon-wrap si-blue"><i class="ri-plane-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Half Day</div><div class="stat-val">{{ number_format($totals['half_day']) }}</div></div><div class="stat-icon-wrap si-amber"><i class="ri-contrast-2-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Worked Hours</div><div class="stat-val" style="font-size:18px;">{{ $totals['worked_label'] }}</div></div><div class="stat-icon-wrap si-purple"><i class="ri-hourglass-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Overtime</div><div class="stat-val" style="font-size:18px;">{{ $totals['overtime_label'] }}</div></div><div class="stat-icon-wrap si-purple"><i class="ri-flashlight-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Missing Checkouts</div><div class="stat-val">{{ number_format($totals['missing_checkouts']) }}</div></div><div class="stat-icon-wrap si-red"><i class="ri-error-warning-fill"></i></div></div>
</div>

<div class="nx-card">
    <div class="nx-card-hdr">
        <div>
            <div class="nx-card-title">Employee Attendance Summary</div>
            <div class="nx-card-sub">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }} · {{ $employees->count() }} employee(s)</div>
        </div>
    </div>

    @if($employees->isEmpty())
        <div class="text-center text-muted py-5">No employees match the selected filters.</div>
    @else
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Half Day</th>
                        <th class="text-center">Leave</th>
                        <th class="text-center">Worked</th>
                        <th class="text-center">Overtime</th>
                        <th class="text-center">Missing<br>Checkout</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        @php $summary = $reports[$employee->id]['summary'] ?? null; @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $employee->full_name }}</div>
                                <small class="text-muted">{{ $employee->employee_code }}</small>
                            </td>
                            <td>{{ $employee->department?->name ?: '—' }}</td>
                            <td>{{ $employee->designation?->name ?: '—' }}</td>
                            @if($summary)
                                <td class="text-center text-success fw-semibold">{{ $summary['present'] }}</td>
                                <td class="text-center text-danger fw-semibold">{{ $summary['absent'] }}</td>
                                <td class="text-center text-warning fw-semibold">{{ $summary['late'] }}</td>
                                <td class="text-center">{{ $summary['half_day'] }}</td>
                                <td class="text-center text-info fw-semibold" @if($summary['leave_breakdown']) title="{{ $summary['leave_breakdown'] }}" @endif>{{ $summary['on_leave'] }}</td>
                                <td class="text-center small">{{ $summary['worked_label'] }}</td>
                                <td class="text-center small">{{ $summary['overtime_label'] }}</td>
                                <td class="text-center">
                                    @if($summary['missing_checkouts'] > 0)
                                        <span class="badge bg-danger-subtle text-danger">{{ $summary['missing_checkouts'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @else
                                <td colspan="8" class="text-center text-muted">No data</td>
                            @endif
                            <td class="text-end">
                                <a href="{{ route('admin.attendances.index', ['employee_id' => $employee->id]) }}" class="btn btn-nx-outline btn-sm" title="View detailed records">
                                    <i class="ri-table-line"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
    <script>
        _componentSelect();
    </script>
@endpush
