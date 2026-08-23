@php
    $periodLabel = $month
        ? \Carbon\Carbon::parse($month . '-01')->format('F Y')
        : $from->format('d M Y') . ' – ' . $to->format('d M Y');
@endphp

<x-print-document
    title="Attendance Report"
    :subtitle="$periodLabel"
    :meta="$filterSummary"
>
    <style>
        /* Landscape orientation + a running "Page X of Y" footer via CSS
           paged-media — the browser's own print engine handles both
           natively, no JS/page-count library needed (same "reuse the
           browser's print pipeline, no new dependency" approach the
           print-document shell itself already documents). */
        @page {
            size: landscape;
            margin: 12mm 10mm;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 9px;
                color: #8a9199;
            }
        }

        .att-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }
        .att-summary-item {
            flex: 1 1 130px;
            border: 1px solid #e2e5ea;
            border-radius: 6px;
            padding: 8px 10px;
        }
        .att-summary-item span {
            display: block;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: .3px;
            color: #8a9199;
            margin-bottom: 2px;
        }
        .att-summary-item strong {
            font-size: 14px;
        }

        .att-table th, .att-table td {
            font-size: 10.5px;
            padding: 5px 7px;
        }
        .att-table td.num, .att-table th.num {
            text-align: center;
            white-space: nowrap;
        }
        .att-table tbody tr:nth-child(even) {
            background: #fbfbfc;
        }

        /* Repeat the table header on every printed page, and never split a
           single employee's row across a page break. */
        .att-table thead { display: table-header-group; }
        .att-table tr { page-break-inside: avoid; }
    </style>

    <div class="att-summary">
        <div class="att-summary-item"><span>Present</span><strong>{{ number_format($totals['present']) }}</strong></div>
        <div class="att-summary-item"><span>Absent</span><strong>{{ number_format($totals['absent']) }}</strong></div>
        <div class="att-summary-item"><span>Late</span><strong>{{ number_format($totals['late']) }}</strong></div>
        <div class="att-summary-item"><span>On Leave</span><strong>{{ number_format($totals['on_leave']) }}</strong></div>
        <div class="att-summary-item"><span>Half Day</span><strong>{{ number_format($totals['half_day']) }}</strong></div>
        <div class="att-summary-item"><span>Worked Hours</span><strong>{{ $totals['worked_label'] }}</strong></div>
        <div class="att-summary-item"><span>Overtime</span><strong>{{ $totals['overtime_label'] }}</strong></div>
        <div class="att-summary-item"><span>Missing Checkouts</span><strong>{{ number_format($totals['missing_checkouts']) }}</strong></div>
    </div>

    @if($employees->isEmpty())
        <p style="text-align:center; color:#8a9199; padding:30px 0;">No employees match the selected filters.</p>
    @else
        <table class="att-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Code</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th class="num">Present</th>
                    <th class="num">Absent</th>
                    <th class="num">Late</th>
                    <th class="num">Half Day</th>
                    <th class="num">Leave</th>
                    <th class="num">Worked</th>
                    <th class="num">Overtime</th>
                    <th class="num">Missing Checkout</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                    @php $summary = $reports[$employee->id]['summary'] ?? null; @endphp
                    <tr>
                        <td>{{ $employee->full_name }}</td>
                        <td>{{ $employee->employee_code }}</td>
                        <td>{{ $employee->department?->name ?: '—' }}</td>
                        <td>{{ $employee->designation?->name ?: '—' }}</td>
                        @if($summary)
                            <td class="num">{{ $summary['present'] }}</td>
                            <td class="num">{{ $summary['absent'] }}</td>
                            <td class="num">{{ $summary['late'] }}</td>
                            <td class="num">{{ $summary['half_day'] }}</td>
                            <td class="num">{{ $summary['on_leave'] }}</td>
                            <td class="num">{{ $summary['worked_label'] }}</td>
                            <td class="num">{{ $summary['overtime_label'] }}</td>
                            <td class="num">{{ $summary['missing_checkouts'] }}</td>
                        @else
                            <td class="num" colspan="8">No data</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-print-document>
