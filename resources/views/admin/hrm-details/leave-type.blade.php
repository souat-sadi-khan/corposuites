@php
    $statusMeta = [
        'approved' => 'bg-success-subtle text-success',
        'pending' => 'bg-warning-subtle text-warning',
        'rejected' => 'bg-danger-subtle text-danger',
    ];
@endphp

<div class="offcanvas-header">
    <div>
        <h5 class="offcanvas-title">{{ $leaveType->name }}</h5>
        <p>Leave history</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
</div>

<div class="offcanvas-body px-3">

    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="border rounded p-2 text-center">
                <div class="fs-5 fw-semibold">{{ $summary['total_requests'] }}</div>
                <div class="text-muted small">Requests</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-2 text-center">
                <div class="fs-5 fw-semibold">{{ $summary['total_days'] }}</div>
                <div class="text-muted small">Total Days</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-2 text-center">
                <div class="fs-5 fw-semibold text-success">{{ $summary['approved'] }}</div>
                <div class="text-muted small">Approved</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-2 text-center">
                <div class="fs-5 fw-semibold text-warning">{{ $summary['pending'] }}</div>
                <div class="text-muted small">Pending</div>
            </div>
        </div>
    </div>

    <form class="row g-2 mb-3 leave-type-filter-form" data-base-url="{{ route('admin.leave-types.details', $leaveType) }}">
        <div class="col">
            <label class="form-label small text-muted mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
        </div>
        <div class="col">
            <label class="form-label small text-muted mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto d-flex align-items-end">
            <button type="submit" class="btn-nx-primary btn-sm">
                <i class="ri-filter-3-line"></i> Filter
            </button>
        </div>
    </form>

    <div class="d-flex gap-2 mb-3">
        <a class="btn-nx-outline btn-sm" href="{{ route('admin.leave-types.export', $leaveType) }}?{{ http_build_query(request()->only('from', 'to')) }}">
            <i class="ri-file-text-line"></i> CSV
        </a>
        <a class="btn-nx-outline btn-sm" href="{{ route('admin.leave-types.export', $leaveType) }}?format=excel&{{ http_build_query(request()->only('from', 'to')) }}">
            <i class="ri-file-excel-2-line"></i> Excel
        </a>
        <button type="button" onclick="window.print()" class="btn-nx-outline btn-sm">
            <i class="ri-printer-line"></i> Print
        </button>
    </div>

    <table class="ractivity-tbl w-100">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Dates</th>
                <th class="text-center">Days</th>
                <th class="text-end">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-semibold flex-shrink-0"
                                  style="width:28px;height:28px;font-size:.7rem;">
                                {{ strtoupper(substr($row->employee?->full_name ?? '?', 0, 1)) }}
                            </span>
                            {{ $row->employee?->full_name ?? 'Unknown employee' }}
                        </div>
                    </td>
                    <td>{{ $row->start_date?->format('d M Y') }} &ndash; {{ $row->end_date?->format('d M Y') }}</td>
                    <td class="text-center">{{ $row->total_days }}</td>
                    <td class="text-end">
                        <span class="badge {{ $statusMeta[$row->approval_status] ?? 'bg-secondary-subtle text-secondary' }}">
                            {{ ucfirst($row->approval_status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        <div class="text-center py-4">
                            <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                            <p class="text-muted mb-0">No leave records in this range.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    (function () {
        // The offcanvas content is swapped in wholesale via .html(), so this
        // runs fresh every time it opens — delegate on document (not the
        // form directly) and .off() first so re-opening never stacks a
        // second handler on top of the last one.
        $(document)
            .off('submit.leaveTypeFilter')
            .on('submit.leaveTypeFilter', '.leave-type-filter-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                var url = $form.data('base-url') + '?' + $form.serialize();

                $('#offcanvas-loader').show();
                $('#sideForm .offcanvas-content').css('opacity', 0.4);

                $.get(url)
                    .done(function (res) {
                        $('#sideForm .offcanvas-content').html(res);
                    })
                    .fail(function () {
                        $('#sideForm .offcanvas-content').css('opacity', 1);
                    })
                    .always(function () {
                        $('#offcanvas-loader').hide();
                    });
            });
    })();
</script>
