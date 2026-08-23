@php
    $codeClass = [
        'present' => 'msheet-p', 'absent' => 'msheet-a', 'late' => 'msheet-l',
        'half_day' => 'msheet-hd', 'early_leave' => 'msheet-el', 'on_leave' => 'msheet-lv',
        'holiday' => 'msheet-h', 'weekly_off' => 'msheet-wo', 'pending' => 'msheet-pd',
    ];
    $legend = [
        'P' => 'Present', 'A' => 'Absent', 'L' => 'Late', 'HD' => 'Half Day',
        'EL' => 'Early Leave', 'LV' => 'On Leave', 'H' => 'Holiday', 'WO' => 'Weekly Off',
    ];
@endphp

@extends('admin.layout.app', ['title' => 'Monthly Attendance Sheet', 'modal' => 'lg'])

@section('content')

<div class="nx-card fm-body mb-3">
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Shift</label>
            <select name="shift_id" class="form-select select form-select-sm">
                <option value="">All Shifts</option>
                @foreach($filters['shifts'] as $shift)
                    <option data-desc="{{ $shift->description }}" value="{{ $shift->id }}" @selected(request('shift_id') == $shift->id)>{{ $shift->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Employee Type</label>
            <select name="employee_type_id" class="form-select select form-select-sm">
                <option value="">All Types</option>
                @foreach($filters['employeeTypes'] as $type)
                    <option data-desc="{{ $type->description }}" value="{{ $type->id }}" @selected(request('employee_type_id') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Employment Status</label>
            <select name="employment_status_id" class="form-select select form-select-sm">
                <option value="">All Statuses</option>
                @foreach($filters['employmentStatuses'] as $es)
                    <option data-desc="{{ $es->description }}" value="{{ $es->id }}" @selected(request('employment_status_id') == $es->id)>{{ $es->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label small mb-1">Department</label>
            <select name="department_id" class="form-select select form-select-sm">
                <option value="">All Departments</option>
                @foreach($filters['departments'] as $dept)
                    <option data-desc="{{ $dept->description }}" value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label small mb-1">Designation</label>
            <select name="designation_id" class="form-select select form-select-sm">
                <option value="">All Designations</option>
                @foreach($filters['designations'] as $designation)
                    <option data-desc="{{ $designation->description }}" value="{{ $designation->id }}" @selected(request('designation_id') == $designation->id)>{{ $designation->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4">
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
                <input class="form-check-input" type="checkbox" name="late_only" value="1" id="msLateOnly" @checked(request('late_only'))>
                <label class="form-check-label small" for="msLateOnly">Late attendance only</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" name="missing_checkout_only" value="1" id="msMissingCheckoutOnly" @checked(request('missing_checkout_only'))>
                <label class="form-check-label small" for="msMissingCheckoutOnly">Missing checkout only</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" name="overtime_only" value="1" id="msOvertimeOnly" @checked(request('overtime_only'))>
                <label class="form-check-label small" for="msOvertimeOnly">Overtime only</label>
            </div>

            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-nx-primary btn-sm"><i class="ri-search-line"></i> View Sheet</button>
                <a href="{{ route('admin.attendances.monthly') }}" class="btn btn-nx-outline btn-sm" title="Reset filters"><i class="ri-refresh-line"></i> Reset</a>
            </div>
        </div>
        <div class="col-12">
            <small class="text-muted">Filters combine together, same as the Attendance Report.</small>
        </div>
    </form>
</div>

<div class="msheet-legend mb-3">
    @foreach($legend as $code => $label)
        @php $bucket = array_search($code, \App\Services\AttendanceReportService::CODES, true); @endphp
        <span class="msheet-legend-item"><span class="msheet-code {{ $codeClass[$bucket] ?? '' }}">{{ $code }}</span> {{ $label }}</span>
    @endforeach
</div>

<div class="nx-card">
    <div class="nx-card-hdr">
        <div>
            <div class="nx-card-title">Monthly Attendance Sheet</div>
            <div class="nx-card-sub">{{ $from->format('F Y') }} · {{ $employees->count() }} employee(s)</div>
        </div>

        <div class="d-flex fm-body align-items-center gap-2">
            <a href="{{ route('admin.attendances.index') }}" class="btn btn-nx-outline btn-sm"><i class="ri-table-line"></i> Detailed List</a>
            <a href="{{ route('admin.attendances.monthly.export.pdf') }}?{{ request()->getQueryString() }}" target="_blank" class="btn btn-nx-primary btn-sm"><i class="ri-file-pdf-2-line"></i> Export PDF</a>
            <a href="{{ route('admin.attendances.monthly.export.excel') }}?{{ request()->getQueryString() }}" class="btn btn-nx-outline btn-sm"><i class="ri-file-excel-2-line"></i> Export Excel</a>
            <a href="{{ route('admin.attendances.monthly.export.csv') }}?{{ request()->getQueryString() }}" class="btn btn-nx-outline btn-sm"><i class="ri-file-list-3-line"></i> Export CSV</a>
        </div>
    </div>

    @if($employees->isEmpty())
        <div class="text-center text-muted py-5">No employees match the selected filters.</div>
    @else
        <div class="msheet-scroll">
            <table class="msheet-table">
                <thead>
                    <tr>
                        <th class="msheet-sticky msheet-sticky-emp text-center">Employee</th>
                        @foreach($from->toPeriod($to) as $date)
                            <th class="{{ \App\Services\WeekendCalendarService::isWeekend($date) ? 'msheet-weekend-col' : '' }} text-center">
                                <div class="msheet-day-num">{{ $date->format('d') }}</div>
                                <div class="msheet-day-dow">{{ $date->format('D') }}</div>
                            </th>
                        @endforeach
                        <th class="msheet-summary-col">P</th>
                        <th class="msheet-summary-col">A</th>
                        <th class="msheet-summary-col">L</th>
                        <th class="msheet-summary-col">LV</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        @php $r = $reports[$employee->id] ?? null; @endphp
                        <tr>
                            <td class="msheet-sticky msheet-sticky-emp">
                                <div class="msheet-emp-wrap">
                                    @if($employee->photo && file_exists(public_path($employee->photo)))
                                        <img src="{{ asset($employee->photo) }}" alt="{{ $employee->full_name }}" class="msheet-avatar">
                                    @else
                                        <span class="msheet-avatar msheet-avatar-fallback">{{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}</span>
                                    @endif
                                    <div class="msheet-emp-text">
                                        <div class="msheet-emp-name">{{ $employee->full_name }} ({{ $employee->employee_code }})</div>
                                        <div class="msheet-emp-code">{{ collect([$employee->designation?->name, $employee->department?->name])->filter()->implode(' · ') ?: '—' }}</div>
                                    </div>
                                </div>
                            </td>

                            @if($r)
                                @foreach($r['days'] as $day)
                                    @php
                                        $rec = $day['record'];
                                        $adjustment = $adjustments->get($employee->id . '|' . $day['date']->toDateString());
                                        $alreadyPending = $adjustment && $adjustment->approval_status === 'pending';
                                        $canRequest = !$day['date']->isAfter(today())
                                            && !$alreadyPending
                                            && Auth::guard('admin')->user()?->can('attendance-adjustment.create');

                                        $tip = $employee->full_name . '|' . $day['date']->format('d M Y')
                                            . '|' . ($rec?->check_in ? \Carbon\Carbon::parse($rec->check_in)->format('h:i A') : '--')
                                            . '|' . ($rec?->check_out ? \Carbon\Carbon::parse($rec->check_out)->format('h:i A') : '--')
                                            . '|' . $day['worked_label']
                                            . '|' . ($rec?->check_in_source_label ?: '--') . ($rec && $rec->punches->count() > 2 ? ' (' . intdiv($rec->punches->count(), 2) . ' sessions)' : '')
                                            . '|' . ($rec?->overtime_hours > 0 ? $rec->overtime_hours . 'h' : '--')
                                            . '|' . ($day['holiday']?->name ?: ucwords(str_replace('_', ' ', $day['bucket'])))
                                            . '|' . ($day['leave_type'] ? $day['leave_type'] . ' · ' . $day['leave_duration_label'] : '—')
                                            . '|' . ($rec?->remarks ?: '—')
                                            . '|' . ($adjustment ? 'Adjustment: ' . ucfirst($adjustment->approval_status) : ($canRequest ? 'Click to request an adjustment' : '—'));
                                    @endphp
                                    <td class="msheet-day-cell {{ $day['is_weekend'] ? 'msheet-weekend-col' : '' }} {{ $canRequest ? 'msheet-day-actionable' : '' }}"
                                        data-tip="{{ $tip }}"
                                        @if($canRequest)
                                            id="openModal"
                                            data-url="{{ route('admin.attendance-adjustments.create', ['employee_id' => $employee->id, 'date' => $day['date']->toDateString()]) }}"
                                        @endif
                                    >
                                        <span class="msheet-code {{ $codeClass[$day['bucket']] ?? '' }}">{{ $day['code'] }}</span>
                                        @if($adjustment)
                                            <span class="msheet-adj-dot msheet-adj-dot-{{ $adjustment->approval_status }}"></span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="msheet-summary-col msheet-sum-p">{{ $r['summary']['present'] }}</td>
                                <td class="msheet-summary-col msheet-sum-a">{{ $r['summary']['absent'] }}</td>
                                <td class="msheet-summary-col msheet-sum-l">{{ $r['summary']['late'] }}</td>
                                <td class="msheet-summary-col msheet-sum-lv">{{ $r['summary']['on_leave'] }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="msheet-tip" id="msheetTip"></div>
@endsection

@push('styles')
<style>
/* Monthly Attendance Sheet — sticky employee/department columns, compact
   day cells, horizontal scroll for the day range, hover detail popover.
   Kept scoped to this page's own classes so it can't affect any other
   table/DataTable in the app. */
.msheet-legend { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 12px; color: var(--tx-2); }
.msheet-legend-item { display: inline-flex; align-items: center; gap: 6px; }

.msheet-scroll { overflow-x: auto; max-width: 100%; }
.msheet-table { border-collapse: separate; border-spacing: 0; width: max-content; min-width: 100%; }
.msheet-table th, .msheet-table td {
    border-bottom: 1px solid var(--border-lt); padding: 6px 8px;
    font-size: 12px; text-align: left; background: var(--bg-surface);
}
.msheet-table thead th {
    position: sticky; top: 0; z-index: 3;
    font-size: 10px; font-weight: 700; color: var(--tx-3);
    padding: 8px 6px; text-transform: uppercase; letter-spacing: .3px;
    border-bottom: 1px solid var(--border);
}
.msheet-day-num { font-size: 12px; font-weight: 700; color: var(--tx-1); }
.msheet-day-dow { font-size: 9px; color: var(--tx-3); }

.msheet-sticky { position: sticky; z-index: 2; text-align: left; background: var(--bg-surface); }
.msheet-sticky-emp { left: 0; min-width: 260px; max-width: 300px; white-space: normal; box-shadow: 2px 0 0 var(--border-lt); }
/* Specificity fix: ".msheet-table thead th" (2 elements) was beating this
   selector (1 element + 1 class) regardless of source order, so the sticky
   Employee header was silently stuck at the SAME z-index as the plain day
   header cells — a tie which the later-in-DOM day cells then won on paint
   order, making them visually slide OVER the sticky Employee column while
   scrolling horizontally instead of underneath it. Matched on the element
   itself (not a descendant combinator) so it wins outright. */
.msheet-table thead th.msheet-sticky { z-index: 5; }
.msheet-emp-wrap { display: flex; align-items: center; gap: 8px; }
.msheet-avatar {
    width: 34px; height: 34px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
    border: 1px solid var(--border-lt);
}
.msheet-avatar-fallback {
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--accent-s); color: var(--accent); font-weight: 700; font-size: 11.5px;
}
.msheet-emp-text { min-width: 0; }
.msheet-emp-name { font-weight: 600; color: var(--tx-1); font-size: 12.5px; white-space: normal; }
.msheet-emp-code { font-size: 10.5px; color: var(--tx-3); white-space: normal; }

.msheet-weekend-col { background: var(--bg-hover); }
.msheet-day-cell { cursor: default; position: relative; }
.msheet-day-cell:hover { background: var(--accent-s); }
/* PART 9 integration: a cell an admin can click to open the "Request
   Adjustment" form pre-filled for that exact employee+day (only rendered
   for a genuinely past-or-today date with no already-pending request). */
.msheet-day-actionable { cursor: pointer; }
.msheet-adj-dot {
    position: absolute; top: 2px; right: 2px;
    width: 6px; height: 6px; border-radius: 50%;
}
.msheet-adj-dot-pending  { background: var(--amber); }
.msheet-adj-dot-approved { background: var(--green); }
.msheet-adj-dot-rejected { background: var(--red); }

.msheet-summary-col { font-weight: 700; min-width: 34px; }
.msheet-sum-p { color: var(--green); }
.msheet-sum-a { color: var(--red); }
.msheet-sum-l { color: var(--amber); }
.msheet-sum-lv { color: var(--blue); }

.msheet-code {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 24px; height: 20px; padding: 0 4px; border-radius: 6px;
    font-size: 10.5px; font-weight: 700;
}
.msheet-p  { background: var(--green-s); color: var(--green); }
.msheet-a  { background: var(--red-s); color: var(--red); }
.msheet-l  { background: var(--amber-s); color: var(--amber); }
.msheet-hd { background: var(--amber-s); color: var(--amber); }
.msheet-el { background: var(--amber-s); color: var(--amber); }
.msheet-lv { background: var(--blue-s); color: var(--blue); }
.msheet-h  { background: rgba(147,51,234,.12); color: #9333ea; }
.msheet-wo { background: var(--bg-hover); color: var(--tx-3); }
.msheet-pd { background: transparent; color: var(--tx-3); border: 1px dashed var(--border); }

.msheet-tip {
    position: fixed; z-index: 2000; display: none;
    background: var(--bg-surface); border: 1px solid var(--border); border-radius: 10px;
    box-shadow: var(--shadow-lg, 0 10px 30px rgba(0,0,0,.18)); padding: 10px 12px;
    font-size: 12px; color: var(--tx-1); max-width: 240px; pointer-events: none;
}
.msheet-tip strong { display: block; font-size: 12.5px; margin-bottom: 4px; }
.msheet-tip .msheet-tip-row { display: flex; justify-content: space-between; gap: 10px; color: var(--tx-2); padding: 1px 0; }
.msheet-tip .msheet-tip-row b { color: var(--tx-1); font-weight: 600; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var $tip = $('#msheetTip');
    var labels = ['', 'Date', 'Check In', 'Check Out', 'Worked', 'Source', 'Overtime', 'Status', 'Leave', 'Notes', 'Adjustment'];

    $(document).on('mouseenter', '.msheet-day-cell', function (e) {
        var parts = ($(this).data('tip') || '').toString().split('|');
        if (parts.length < 11) return;

        var html = '<strong>' + parts[0] + '</strong>';
        for (var i = 1; i < parts.length; i++) {
            // Skip the "Leave" row entirely on a non-leave day rather than
            // showing a meaningless "—" on every present/absent/weekend
            // tooltip too (PART 4: don't overload every cell with info).
            if (labels[i] === 'Leave' && parts[i] === '—') continue;
            if (labels[i] === 'Source' && parts[i] === '--') continue;
            html += '<div class="msheet-tip-row"><span>' + labels[i] + '</span><b>' + parts[i] + '</b></div>';
        }
        $tip.html(html).css('display', 'block');
    });

    $(document).on('mousemove', '.msheet-day-cell', function (e) {
        var top = e.clientY + 16;
        var left = e.clientX + 16;
        if (left + 240 > window.innerWidth) left = e.clientX - 256;
        $tip.css({ top: top + 'px', left: left + 'px' });
    });

    $(document).on('mouseleave', '.msheet-day-cell', function () {
        $tip.css('display', 'none');
    });
})();
_componentSelect();

// This page has no DataTable of its own, so unlike every other admin list
// screen (which calls this from a drawCallback), the shared "#openModal"
// remote-modal click handler is never bound automatically — wired here
// explicitly so the day cells' "Request Adjustment" quick action works.
if (typeof _componentRemoteModalLoadAfterAjax === 'function') {
    _componentRemoteModalLoadAfterAjax();
}

// The shared .ajax-form success handler only reloads a DataTable — this
// page's sheet is a plain server-rendered table, so after a successful
// adjustment request from a day cell, refresh the page so the new
// pending/approved/rejected dot actually shows up without a manual reload.
$(document).ajaxSuccess(function (event, xhr, settings) {
    if (settings.url && settings.url.indexOf('/attendance-adjustments') !== -1 && xhr.responseJSON && xhr.responseJSON.status) {
        setTimeout(function () { window.location.reload(); }, 600);
    }
});
</script>
@endpush
