<x-print-document
    :title="$salaryComponent->name . ' — Component History'"
    subtitle="Salary Component Usage Report"
    :meta="array_filter([
        'Component Code' => $salaryComponent->code,
        'Type' => ucfirst($salaryComponent->type),
        'Date Range' => (request('from') || request('to'))
            ? (request('from', 'Start') . ' — ' . request('to', 'Today'))
            : 'All time',
        'Total Entries' => $summary['total_entries'],
        'Employees' => $summary['employee_count'],
        'Total Amount' => format_currency($summary['total_amount']),
    ])"
>
    <table>
        <thead>
            <tr>
                <th style="width:8%;">#</th>
                <th>Employee</th>
                <th style="width:20%;">Effective Date</th>
                <th style="width:18%;" class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->salaryStructure?->employee?->full_name ?? 'Unknown employee' }}</td>
                    <td>{{ $row->salaryStructure?->effective_date?->format('d M Y') }}</td>
                    <td style="text-align:right;">{{ format_currency($row->amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;">No component records in this range.</td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->count())
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right; font-weight:700;">Total</td>
                    <td style="text-align:right; font-weight:700;">{{ format_currency($summary['total_amount']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</x-print-document>
