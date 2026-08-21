<x-history-offcanvas
    :title="$salaryComponent->name"
    subtitle="Component history"
    :stats="[
        ['value' => $summary['total_entries'], 'label' => 'Entries'],
        ['value' => $summary['employee_count'], 'label' => 'Employees'],
        ['value' => format_currency($summary['total_amount']), 'label' => 'Total Amount', 'accent' => true],
    ]"
    :filter-action="route('admin.salary-components.details', $salaryComponent)"
    :export-links="[
        [
            'url' => route('admin.salary-components.export', $salaryComponent) . '?' . http_build_query(request()->only('from', 'to')),
            'label' => 'CSV',
            'icon' => 'ri-file-text-line',
        ],
        [
            'url' => route('admin.salary-components.export', $salaryComponent) . '?' . http_build_query(array_merge(request()->only('from', 'to'), ['format' => 'excel'])),
            'label' => 'Excel',
            'icon' => 'ri-file-excel-2-line',
        ],
        [
            'url' => route('admin.salary-components.print', $salaryComponent) . '?' . http_build_query(request()->only('from', 'to')),
            'label' => 'PDF',
            'icon' => 'ri-file-pdf-2-line',
            'target' => '_blank',
        ],
    ]"
>
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
                    <td class="text-end fw-semibold">{{ format_currency($row->amount) }}</td>
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
</x-history-offcanvas>
