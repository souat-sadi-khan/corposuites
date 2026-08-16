@extends('admin.layout.app', ['title' => 'Holidays Calendar'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-filter-wrap">
            
        </div>

        <div class="tl-spacer"></div>

        <a href="{{ route('admin.holidays.index') }}" class="btn-nx-primary">
            <i class="ri-list-check-2 me-1"></i>
            List View
        </a>
    </div>

    <div class="nx-card tl-card p-2">
        <div id="holidaysCalendar" data-url="{{ route('admin.holidays.calendar-events') }}"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/holidays-calendar.js') }}"></script>
@endpush
