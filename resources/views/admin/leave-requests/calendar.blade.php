@extends('admin.layout.app', ['title' => 'Leave Calendar'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-spacer"></div>

        <a href="{{ route('admin.leave-requests.index') }}" class="btn-nx-primary">
            <i class="ri-list-check-2 me-1"></i>
            List View
        </a>
    </div>

    <div class="nx-card tl-card p-3">
        <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
            <span class="d-inline-flex align-items-center gap-1">
                <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#16a34a;"></span>
                <small>Approved</small>
            </span>
            <span class="d-inline-flex align-items-center gap-1">
                <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#f59e0b;"></span>
                <small>Pending</small>
            </span>
        </div>

        <div id="leaveCalendar" data-url="{{ route('admin.leave-requests.calendar-events') }}"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/leave-calendar.js') }}"></script>
@endpush
