@extends('admin.layout.app', ['title' => 'Leave Calendar', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="lc-stats">
            <div class="lc-stat lc-stat-total">
                <span class="lc-stat-val" id="lcStatTotal">0</span>
                <span class="lc-stat-lbl">Leave entries in view</span>
            </div>
            <div class="lc-stat lc-stat-pending">
                <span class="lc-stat-val" id="lcStatPending">0</span>
                <span class="lc-stat-lbl">Pending</span>
            </div>
            <div class="lc-stat lc-stat-approved">
                <span class="lc-stat-val" id="lcStatApproved">0</span>
                <span class="lc-stat-lbl">Approved</span>
            </div>
            <div class="lc-stat lc-stat-people">
                <span class="lc-stat-val" id="lcStatPeople">0</span>
                <span class="lc-stat-lbl">Employees</span>
            </div>
        </div>

        <div class="tl-spacer"></div>

        <a href="{{ route('admin.leave-requests.index') }}" class="btn-nx-outline">
            <i class="ri-list-check-2 me-1"></i> List View
        </a>
    </div>

    <div class="nx-card lc-filter-card">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Employee</label>
                <select id="lcEmployee" class="form-select select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Leave Type</label>
                <select id="lcLeaveType" class="form-select select form-select-sm">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Department</label>
                <select id="lcDepartment" class="form-select select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex align-items-center gap-2">
                <div class="form-check form-switch mb-0">
                    <input type="checkbox" class="form-check-input" id="lcShowRejected">
                    <label class="form-check-label small" for="lcShowRejected">Show Rejected/Cancelled</label>
                </div>
            </div>
        </div>
    </div>

    <div class="nx-card lc-legend-card">
        <div class="lc-legend-title"><i class="ri-palette-line"></i> Leave Types</div>
        <div class="lc-legend">
            @foreach($leaveTypes as $type)
                <span class="lc-legend-chip">
                    <span class="lc-legend-dot" style="background:{{ $type->calendar_color }}"></span>
                    {{ $type->name }}
                </span>
            @endforeach
        </div>
        <div class="lc-legend-status">
            <span class="lc-status-chip lc-status-approved"><i class="ri-checkbox-circle-fill"></i> Approved (solid)</span>
            <span class="lc-status-chip lc-status-pending"><i class="ri-time-line"></i> Pending (striped)</span>
            <span class="lc-status-chip lc-status-rejected"><i class="ri-close-circle-line"></i> Rejected (faded)</span>
            <span class="lc-status-chip lc-status-cancelled"><i class="ri-forbid-line"></i> Cancelled (faded)</span>
        </div>
    </div>

    <div class="nx-card lc-cal-card">
        <div id="leaveCalendar" data-url="{{ route('admin.leave-requests.calendar-events') }}"></div>
    </div>

    <!-- Hidden trigger reused by leave-calendar.js so an event click opens
         the SAME "View Details" remote modal the list view's own action
         button uses — one shared implementation, two entry points. -->
    <button type="button" id="openModal" data-url="" class="d-none"></button>
@endsection

@push('styles')
<style>
    .lc-stats { display: flex; gap: 18px; flex-wrap: wrap; }
    .lc-stat { display: flex; flex-direction: column; padding: 4px 14px; border-right: 1px solid var(--border-lt); }
    .lc-stat:last-child { border-right: none; }
    .lc-stat-val { font-size: 19px; font-weight: 800; color: var(--tx-1); line-height: 1.1; }
    .lc-stat-lbl { font-size: 10.5px; color: var(--tx-3); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; margin-top: 2px; }
    .lc-stat-pending .lc-stat-val { color: #f59e0b; }
    .lc-stat-approved .lc-stat-val { color: var(--green); }

    .lc-filter-card { padding: 14px 16px; margin-bottom: 12px; }
    .lc-legend-card { padding: 12px 16px; margin-bottom: 14px; }
    .lc-legend-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--tx-3); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .lc-legend { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
    .lc-legend-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; color: var(--tx-2); background: var(--bg-base); border: 1px solid var(--border-lt); border-radius: 999px; padding: 4px 10px; }
    .lc-legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .lc-legend-status { display: flex; flex-wrap: wrap; gap: 14px; border-top: 1px dashed var(--border-lt); padding-top: 9px; }
    .lc-status-chip { font-size: 11px; color: var(--tx-3); display: inline-flex; align-items: center; gap: 5px; font-weight: 600; }
    .lc-status-chip.lc-status-approved i { color: var(--green); }
    .lc-status-chip.lc-status-pending i { color: #f59e0b; }
    .lc-status-chip.lc-status-rejected i, .lc-status-chip.lc-status-cancelled i { color: var(--red); }

    .lc-cal-card { padding: 18px; }

    /* ===== FullCalendar theme overrides — make it match this app's own
       design language (rounded surfaces, subtle borders, accent colour)
       instead of FullCalendar's generic default look. ===== */
    #leaveCalendar .fc { font-family: inherit; --fc-border-color: var(--border-lt); --fc-page-bg-color: transparent; --fc-neutral-bg-color: var(--bg-base); --fc-today-bg-color: var(--accent-s); }
    #leaveCalendar .fc-toolbar-title { font-size: 18px !important; font-weight: 800; color: var(--tx-1); }
    #leaveCalendar .fc-button { background: var(--bg-surface) !important; border: 1px solid var(--border) !important; color: var(--tx-2) !important; box-shadow: none !important; text-transform: capitalize; font-weight: 600; font-size: 12.5px !important; padding: 6px 12px !important; border-radius: 8px !important; }
    #leaveCalendar .fc-button:hover { background: var(--bg-base) !important; color: var(--tx-1) !important; }
    #leaveCalendar .fc-button-active, #leaveCalendar .fc-button:focus { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; box-shadow: none !important; }
    #leaveCalendar .fc-daygrid-day-frame { border-radius: 6px; }
    #leaveCalendar .fc-col-header-cell { background: var(--bg-base); }
    #leaveCalendar .fc-col-header-cell-cushion { color: var(--tx-3); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; padding: 8px 0; }
    #leaveCalendar .fc-daygrid-day-number { color: var(--tx-2); font-weight: 600; font-size: 12.5px; padding: 6px; }
    #leaveCalendar .fc-day-today .fc-daygrid-day-number { color: var(--accent); font-weight: 800; }
    #leaveCalendar .fc-daygrid-day.fc-day-today { background: var(--accent-s) !important; }
    #leaveCalendar .fc-scrollgrid { border-radius: 10px; overflow: hidden; }

    /* Event chips — coloured by leave type, styled by status */
    #leaveCalendar .lc-event { border-radius: 6px !important; border-width: 0 !important; border-left: 3px solid transparent !important; padding: 1px 2px; font-size: 11.5px !important; font-weight: 600; }
    #leaveCalendar .lc-event .fc-event-main { padding: 1px 4px; }
    #leaveCalendar .lc-event-pending { background-image: repeating-linear-gradient(45deg, rgba(255,255,255,.25) 0 6px, transparent 6px 12px) !important; border-left-color: #f59e0b !important; }
    #leaveCalendar .lc-event-approved { border-left-color: var(--green) !important; }
    #leaveCalendar .lc-event-rejected, #leaveCalendar .lc-event-cancelled { opacity: .45 !important; text-decoration: line-through; border-left-color: var(--red) !important; }
    #leaveCalendar .fc-list-event:hover td, #leaveCalendar .fc-daygrid-event:hover { cursor: pointer; filter: brightness(1.08); }

    @media (max-width: 767px) {
        .lc-stats { gap: 10px; }
        .lc-stat { padding: 2px 8px; }
    }
</style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/leave-calendar.js') }}"></script>
    <script>_componentSelect();</script>
@endpush
