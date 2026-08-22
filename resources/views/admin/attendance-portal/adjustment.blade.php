@extends('admin.layout.app', ['title' => 'Request Adjustment'])

@section('content')

<div class="myatt-adj-wrap">
    <div class="nx-card myatt-adj-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title"><i class="ri-edit-2-line"></i> Request Attendance Adjustment</div>
                <div class="nx-card-sub">{{ $date->format('l, d F Y') }}</div>
            </div>
            <a href="{{ route('admin.attendance-portal.index') }}" class="btn btn-nx-outline btn-sm">
                <i class="ri-arrow-left-line"></i> Back
            </a>
        </div>

        <div class="p-3">
            @if($pendingExists)
                <div class="myatt-adj-notice">
                    <i class="ri-time-line"></i>
                    You already have a pending adjustment request for this date. Please wait for it to be reviewed before submitting another.
                </div>
            @else
                @if($attendance)
                    <div class="myatt-adj-current">
                        <span class="myatt-adj-current-lbl">Currently recorded</span>
                        <span><i class="ri-login-circle-line"></i> {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') : '--' }}</span>
                        <span><i class="ri-logout-circle-line"></i> {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') : '--' }}</span>
                        <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $attendance->attendance_status)) }}</span>
                    </div>
                @else
                    <div class="myatt-adj-current">
                        <span class="myatt-adj-current-lbl">Currently recorded</span>
                        <span class="text-muted">No attendance record exists for this date.</span>
                    </div>
                @endif

                <form id="adjustmentForm" class="row g-3 mt-1">
                    <input type="hidden" name="adjustment_date" value="{{ $date->toDateString() }}">

                    <div class="col-6">
                        <label class="form-label small">Requested Check In</label>
                        <input type="time" name="requested_check_in" class="form-control" value="{{ $attendance?->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Requested Check Out</label>
                        <input type="time" name="requested_check_out" class="form-control" value="{{ $attendance?->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Explain why this correction is needed (e.g. forgot to check out, device malfunction)…" required></textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" id="adjSubmitBtn" class="myatt-btn myatt-btn-out myatt-adj-submit">
                            <i class="ri-send-plane-fill"></i> <span>Submit Request</span>
                        </button>
                    </div>

                    <div class="col-12">
                        <div class="myatt-flash" id="adjMessage"></div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#adjustmentForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#adjSubmitBtn');
    $btn.prop('disabled', true);
    $('#adjMessage').removeClass('is-success is-error').text('Submitting …');

    $.post('{{ route('admin.attendance-portal.adjustment.store') }}', $(this).serialize())
        .done(function (r) {
            $('#adjMessage').removeClass('is-error is-success').addClass(r.status ? 'is-success' : 'is-error').text(r.message);
            if (r.status) {
                setTimeout(function () { window.location.href = '{{ route('admin.attendance-portal.index') }}'; }, 900);
            } else {
                $btn.prop('disabled', false);
            }
        })
        .fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to submit request.';
            $('#adjMessage').removeClass('is-success').addClass('is-error').text(message);
            $btn.prop('disabled', false);
        });
});
</script>
@endpush
