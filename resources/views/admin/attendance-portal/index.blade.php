@php
    $bucketMeta = [
        'present' => ['label' => 'Present', 'class' => 'myatt-badge-present', 'icon' => 'ri-checkbox-circle-fill'],
        'absent' => ['label' => 'Absent', 'class' => 'myatt-badge-absent', 'icon' => 'ri-close-circle-fill'],
        'late' => ['label' => 'Late', 'class' => 'myatt-badge-late', 'icon' => 'ri-alarm-warning-fill'],
        'half_day' => ['label' => 'Half Day', 'class' => 'myatt-badge-late', 'icon' => 'ri-contrast-2-fill'],
        'early_leave' => ['label' => 'Early Leave', 'class' => 'myatt-badge-late', 'icon' => 'ri-logout-circle-line'],
        'on_leave' => ['label' => 'On Leave', 'class' => 'myatt-badge-leave', 'icon' => 'ri-plane-fill'],
        'holiday' => ['label' => 'Holiday', 'class' => 'myatt-badge-holiday', 'icon' => 'ri-sun-fill'],
        'weekly_off' => ['label' => 'Weekly Off', 'class' => 'myatt-badge-off', 'icon' => 'ri-moon-clear-fill'],
        'pending' => ['label' => 'Not Marked', 'class' => 'myatt-badge-pending', 'icon' => 'ri-more-line'],
    ];
@endphp

@extends('admin.layout.app', ['title' => 'My Attendance'])

@section('content')

<div class="myatt-hero">
    <div class="myatt-hero-left">
        <div class="myatt-avatar">{{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}</div>
        <div>
            <h4>{{ $employee->full_name }}</h4>
            <p>
                <i class="ri-calendar-line"></i> {{ now()->format('l, d F Y') }}
                <span class="myatt-sep">·</span>
                <i class="ri-shining-2-line"></i> {{ $employee->shift?->name ?: 'Default Shift' }}
            </p>
        </div>
    </div>

    <div class="myatt-hero-actions">
        <button id="checkIn" class="myatt-btn myatt-btn-in" {{ $attendance?->check_in ? 'disabled' : '' }}>
            <i class="ri-login-circle-fill"></i>
            <span>Check In</span>
            @if($attendance?->check_in)<small>{{ \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') }}</small>@endif
        </button>
        <button id="checkOut" class="myatt-btn myatt-btn-out" {{ !$attendance?->check_in || $attendance?->check_out ? 'disabled' : '' }}>
            <i class="ri-logout-circle-fill"></i>
            <span>Check Out</span>
            @if($attendance?->check_out)<small>{{ \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') }}</small>@endif
        </button>
    </div>
</div>
<p id="attendanceMessage" class="myatt-flash"></p>

<div class="nx-card fm-body mb-3">
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button class="btn btn-nx-primary btn-sm flex-fill"><i class="ri-search-line"></i> View Report</button>
            <a href="{{ route('admin.attendance-portal.index') }}" class="btn btn-nx-outline btn-sm" title="Reset to current month"><i class="ri-refresh-line"></i></a>
        </div>
        <div class="col-12">
            <small class="text-muted">Fill Date From / Date To for a custom range (overrides Month), or use Month alone for a full calendar month.</small>
        </div>
    </form>
</div>

<div class="stats-grid myatt-stats mb-3">
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Present</div><div class="stat-val">{{ $report['summary']['present'] }}</div></div><div class="stat-icon-wrap si-green"><i class="ri-checkbox-circle-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Absent</div><div class="stat-val">{{ $report['summary']['absent'] }}</div></div><div class="stat-icon-wrap si-red"><i class="ri-close-circle-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Late</div><div class="stat-val">{{ $report['summary']['late'] }}</div></div><div class="stat-icon-wrap si-amber"><i class="ri-alarm-warning-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">On Leave</div><div class="stat-val">{{ $report['summary']['on_leave'] }}</div></div><div class="stat-icon-wrap si-blue"><i class="ri-plane-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Worked Hours</div><div class="stat-val myatt-stat-val-sm">{{ $report['summary']['worked_label'] }}</div></div><div class="stat-icon-wrap si-purple"><i class="ri-hourglass-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Overtime</div><div class="stat-val myatt-stat-val-sm">{{ $report['summary']['overtime_label'] }}</div></div><div class="stat-icon-wrap si-purple"><i class="ri-flashlight-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Half Day</div><div class="stat-val">{{ $report['summary']['half_day'] }}</div></div><div class="stat-icon-wrap si-amber"><i class="ri-contrast-2-fill"></i></div></div>
    <div class="stat-card"><div class="stat-content"><div class="stat-lbl">Missing Checkout</div><div class="stat-val">{{ $report['summary']['missing_checkouts'] }}</div></div><div class="stat-icon-wrap si-red"><i class="ri-error-warning-fill"></i></div></div>
</div>

<div class="nx-card">
    <div class="nx-card-hdr">
        <div>
            <div class="nx-card-title">Attendance Details</div>
            <div class="nx-card-sub">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Worked</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $adjBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                @endphp
                @forelse($report['days'] as $day)
                    @php
                        $meta = $bucketMeta[$day['bucket']] ?? $bucketMeta['pending'];
                        $dateKey = $day['date']->toDateString();
                        $adjustment = $adjustments->get($dateKey);
                        $isPastDay = $day['bucket'] !== 'pending';
                        $missingCheckout = $day['record']?->check_in && !$day['record']?->check_out && $isPastDay;
                    @endphp
                    <tr class="{{ $day['is_weekend'] ? 'myatt-row-weekend' : '' }}">
                        <td>{{ $day['date']->format('d M Y') }}</td>
                        <td>{{ $day['date']->format('D') }}</td>
                        <td>
                            <span class="myatt-badge {{ $meta['class'] }}">
                                <i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                            </span>
                            @if($missingCheckout)
                                <span class="myatt-badge myatt-badge-absent" title="Check-in recorded but no check-out">
                                    <i class="ri-error-warning-fill"></i> Missing Checkout
                                </span>
                            @endif
                        </td>
                        <td>{{ $day['record']?->check_in ? \Carbon\Carbon::parse($day['record']->check_in)->format('h:i A') : '--' }}</td>
                        <td>{{ $day['record']?->check_out ? \Carbon\Carbon::parse($day['record']->check_out)->format('h:i A') : '--' }}</td>
                        <td>{{ $day['worked_label'] }}</td>
                        <td class="text-muted small">
                            {{ $day['holiday']?->name ?: $day['record']?->remarks }}
                            @if($adjustment)
                                <span class="badge bg-{{ $adjBadge[$adjustment->approval_status] ?? 'secondary' }}-subtle text-{{ $adjBadge[$adjustment->approval_status] ?? 'secondary' }} ms-1">
                                    Adjustment: {{ ucfirst($adjustment->approval_status) }}
                                </span>
                            @elseif($isPastDay)
                                <a href="{{ route('admin.attendance-portal.adjustment.form', ['date' => $dateKey]) }}" class="myatt-adj-link">
                                    <i class="ri-edit-2-line"></i> Request Adjustment
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No records for this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function punch(url){
    var $btn = event ? $(event.currentTarget) : null;
    if(!navigator.geolocation){$('#attendanceMessage').removeClass('is-success').addClass('is-error').text('Location services are unavailable.');return;}
    $('#checkIn, #checkOut').prop('disabled', true);
    $('#attendanceMessage').removeClass('is-error is-success').text('Working …');
    navigator.geolocation.getCurrentPosition(function(p){
        $.post(url,{latitude:p.coords.latitude,longitude:p.coords.longitude,source:'browser_geolocation'})
            .done(function(r){
                $('#attendanceMessage').removeClass('is-error is-success').addClass(r.status ? 'is-success' : 'is-error').text(r.message);
                if (r.status) { setTimeout(function(){ location.reload(); }, 700); }
                else { $('#checkIn').prop('disabled', {{ $attendance?->check_in ? 'true' : 'false' }}); $('#checkOut').prop('disabled', {{ !$attendance?->check_in || $attendance?->check_out ? 'true' : 'false' }}); }
            })
            .fail(function(x){
                $('#attendanceMessage').removeClass('is-success').addClass('is-error').text(x.responseJSON?.message||'Unable to record attendance.');
                $('#checkIn').prop('disabled', {{ $attendance?->check_in ? 'true' : 'false' }});
                $('#checkOut').prop('disabled', {{ !$attendance?->check_in || $attendance?->check_out ? 'true' : 'false' }});
            });
    },function(){
        $('#attendanceMessage').removeClass('is-success').addClass('is-error').text('Please allow location access to continue.');
        $('#checkIn').prop('disabled', {{ $attendance?->check_in ? 'true' : 'false' }});
        $('#checkOut').prop('disabled', {{ !$attendance?->check_in || $attendance?->check_out ? 'true' : 'false' }});
    },{enableHighAccuracy:true,timeout:15000});
}
$('#checkIn').click(function(e){ punch('{{ route('admin.attendance-portal.check-in') }}'); });
$('#checkOut').click(function(e){ punch('{{ route('admin.attendance-portal.check-out') }}'); });
</script>
@endpush
