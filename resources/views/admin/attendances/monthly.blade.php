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

@extends('admin.layout.app', ['title' => 'Monthly Attendance Sheet'])

@section('content')

<div class="nx-card fm-body mb-3">
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Employee</label>
            <select name="employee_id" class="form-select form-select-sm">
                <option value="">All Employees</option>
                @foreach($allEmployeesForFilter as $emp)
                    <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button class="btn btn-nx-primary btn-sm flex-fill"><i class="ri-search-line"></i> View Sheet</button>
            <a href="{{ route('admin.attendances.monthly') }}" class="btn btn-nx-outline btn-sm" title="Reset filters"><i class="ri-refresh-line"></i></a>
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
        <a href="{{ route('admin.attendances.index') }}" class="btn btn-nx-outline btn-sm"><i class="ri-table-line"></i> Detailed List</a>
    </div>

    @if($employees->isEmpty())
        <div class="text-center text-muted py-5">No employees match the selected filters.</div>
    @else
        <div class="msheet-scroll">
            <table class="msheet-table">
                <thead>
                    <tr>
                        <th class="msheet-sticky msheet-sticky-emp">Employee</th>
                        <th class="msheet-sticky msheet-sticky-dept">Department</th>
                        @foreach($from->toPeriod($to) as $date)
                            <th class="{{ in_array($date->dayOfWeek, $weekendDays, true) ? 'msheet-weekend-col' : '' }}">
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
                                <div class="msheet-emp-name">{{ $employee->full_name }}</div>
                                <div class="msheet-emp-code">{{ $employee->employee_code }}</div>
                            </td>
                            <td class="msheet-sticky msheet-sticky-dept">{{ $employee->department?->name ?: '—' }}</td>

                            @if($r)
                                @foreach($r['days'] as $day)
                                    @php
                                        $rec = $day['record'];
                                        $tip = $employee->full_name . '|' . $day['date']->format('d M Y')
                                            . '|' . ($rec?->check_in ? \Carbon\Carbon::parse($rec->check_in)->format('h:i A') : '--')
                                            . '|' . ($rec?->check_out ? \Carbon\Carbon::parse($rec->check_out)->format('h:i A') : '--')
                                            . '|' . $day['worked_label']
                                            . '|' . ($rec?->overtime_hours > 0 ? $rec->overtime_hours . 'h' : '--')
                                            . '|' . ($day['holiday']?->name ?: ucwords(str_replace('_', ' ', $day['bucket'])))
                                            . '|' . ($rec?->remarks ?: '—');
                                    @endphp
                                    <td class="msheet-day-cell {{ $day['is_weekend'] ? 'msheet-weekend-col' : '' }}" data-tip="{{ $tip }}">
                                        <span class="msheet-code {{ $codeClass[$day['bucket']] ?? '' }}">{{ $day['code'] }}</span>
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
    font-size: 12px; text-align: center; white-space: nowrap; background: var(--bg-surface);
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
.msheet-sticky-emp { left: 0; min-width: 170px; max-width: 170px; box-shadow: 2px 0 0 var(--border-lt); }
.msheet-sticky-dept { left: 170px; min-width: 120px; max-width: 120px; box-shadow: 2px 0 0 var(--border-lt); color: var(--tx-2); }
thead .msheet-sticky { z-index: 4; }
.msheet-emp-name { font-weight: 600; color: var(--tx-1); font-size: 12.5px; }
.msheet-emp-code { font-size: 10.5px; color: var(--tx-3); }

.msheet-weekend-col { background: var(--bg-hover); }
.msheet-day-cell { cursor: default; }
.msheet-day-cell:hover { background: var(--accent-s); }

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
    var labels = ['', 'Date', 'Check In', 'Check Out', 'Worked', 'Overtime', 'Status', 'Notes'];

    $(document).on('mouseenter', '.msheet-day-cell', function (e) {
        var parts = ($(this).data('tip') || '').toString().split('|');
        if (parts.length < 8) return;

        var html = '<strong>' + parts[0] + '</strong>';
        for (var i = 1; i < parts.length; i++) {
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
</script>
@endpush
