<div class="offcanvas-header">
    <div>
        <h5 class="offcanvas-title">{{ $salaryComponent->name }}</h5>
        <p>Component history</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
</div>

<div class="offcanvas-body px-3">

    <div class="row g-2 mb-4">
        <div class="col-4">
            <div class="border rounded p-2 text-center">
                <div class="fs-5 fw-semibold">{{ $summary['total_entries'] }}</div>
                <div class="text-muted small">Entries</div>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded p-2 text-center">
                <div class="fs-5 fw-semibold">{{ $summary['employee_count'] }}</div>
                <div class="text-muted small">Employees</div>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded p-2 text-center">
                <div class="fs-5 fw-semibold">{{ number_format($summary['total_amount'], 2) }}</div>
                <div class="text-muted small">Total Amount</div>
            </div>
        </div>
    </div>

    <form class="row g-2 mb-3 salary-component-filter-form" data-base-url="{{ route('admin.salary-components.details', $salaryComponent) }}">
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
        <a class="btn-nx-outline btn-sm" href="{{ route('admin.salary-components.export', $salaryComponent) }}?{{ http_build_query(request()->only('from', 'to')) }}">
            <i class="ri-file-text-line"></i> CSV
        </a>
        <a class="btn-nx-outline btn-sm" href="{{ route('admin.salary-components.export', $salaryComponent) }}?format=excel&{{ http_build_query(request()->only('from', 'to')) }}">
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
                <th>Effective Date</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-semibold flex-shrink-0"
                                  style="width:28px;height:28px;font-size:.7rem;">
                                {{ strtoupper(substr($row->salaryStructure?->employee?->full_name ?? '?', 0, 1)) }}
                            </span>
                            {{ $row->salaryStructure?->employee?->full_name ?? 'Unknown employee' }}
                        </div>
                    </td>
                    <td>{{ $row->salaryStructure?->effective_date?->format('d M Y') }}</td>
                    <td class="text-end fw-semibold">{{ number_format($row->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">
                        <div class="text-center py-4">
                            <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                            <p class="text-muted mb-0">No component records in this range.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    (function () {
        $(document)
            .off('submit.salaryComponentFilter')
            .on('submit.salaryComponentFilter', '.salary-component-filter-form', function (e) {
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
