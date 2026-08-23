@php
    // Same bucket->CSS-class / letter-code mapping the on-screen sheet uses
    // (monthly.blade.php), reused here as-is so the printed badges mean
    // exactly the same thing as the ones on screen — never re-derived.
    $codeClass = [
        'present' => 'mp-p', 'absent' => 'mp-a', 'late' => 'mp-l',
        'half_day' => 'mp-hd', 'early_leave' => 'mp-el', 'on_leave' => 'mp-lv',
        'holiday' => 'mp-h', 'weekly_off' => 'mp-wo', 'pending' => 'mp-pd',
    ];
    $legend = [
        'P' => 'Present', 'A' => 'Absent', 'L' => 'Late', 'HD' => 'Half Day',
        'EL' => 'Early Leave', 'LV' => 'On Leave', 'H' => 'Holiday', 'WO' => 'Weekly Off',
    ];

    $days = collect($from->toPeriod($to));
    $dayCount = max($days->count(), 1);

    // table-layout: fixed with computed percentage widths so the sheet
    // ALWAYS fits exactly one landscape page wide regardless of how many
    // days are in the month (28–31) — the on-screen version can rely on
    // horizontal scroll (.msheet-scroll) for this, a printed page can't.
    $empColWidth = 13;
    $sumColWidth = 3; // × 4 summary columns
    $dayColWidth = round((100 - $empColWidth - ($sumColWidth * 4)) / $dayCount, 3);
@endphp

<x-print-document
    title="Monthly Attendance Sheet"
    :subtitle="$from->format('F Y') . ' · ' . $employees->count() . ' employee(s)'"
    :meta="$filterSummary"
>
    <style>
        /* Landscape orientation + a running "Page X of Y" footer via CSS
           paged-media — same approach the Attendance Report's own PDF
           export already uses, no JS/page-count library needed. */
        @page {
            size: landscape;
            margin: 10mm 8mm;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 9px;
                color: #8a9199;
            }
        }

        .mp-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 14px;
            font-size: 9.5px;
            color: #667085;
            margin-bottom: 14px;
        }
        .mp-legend-item { display: inline-flex; align-items: center; gap: 5px; }
        .mp-legend-item .mp-code { min-width: 9px; }

        .mp-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .mp-table th, .mp-table td {
            border: 1px solid #e2e5ea;
            padding: 3px 2px;
            text-align: center;
            font-size: 8px;
            vertical-align: middle;
        }
        .mp-table th {
            background: #f5f6f8;
            font-weight: 700;
            font-size: 7.5px;
        }
        .mp-emp-col { width: {{ $empColWidth }}%; text-align: left; padding-left: 6px !important; }
        .mp-day-col { width: {{ $dayColWidth }}%; }
        .mp-sum-col { width: {{ $sumColWidth }}%; }
        .mp-weekend-col { background: #f7f8fa; }

        .mp-emp-name { font-weight: 700; font-size: 9.5px; color: #1f2430; }
        .mp-emp-sub { font-size: 8px; color: #8a9199; }

        .mp-day-num { font-weight: 700; }
        .mp-day-dow { font-size: 6.5px; color: #8a9199; text-transform: uppercase; }

        .mp-code {
            display: inline-block;
            min-width: 14px;
            padding: 1px 2px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: 700;
            line-height: 1.3;
        }
        .mp-p  { background: #dcfce7; color: #16a34a; }
        .mp-a  { background: #fee2e2; color: #dc2626; }
        .mp-l  { background: #fef3c7; color: #d97706; }
        .mp-hd { background: #fef3c7; color: #d97706; }
        .mp-el { background: #fef3c7; color: #d97706; }
        .mp-lv { background: #dbeafe; color: #2563eb; }
        .mp-h  { background: #f3e8ff; color: #9333ea; }
        .mp-wo { background: #f1f5f9; color: #64748b; }
        .mp-pd { background: transparent; color: #b0b6bd; border: 1px dashed #d7dbe0; }

        .mp-adj-dot {
            display: inline-block;
            width: 4px; height: 4px;
            border-radius: 50%;
            margin-left: 1px;
            vertical-align: middle;
        }
        .mp-adj-dot-pending  { background: #d97706; }
        .mp-adj-dot-approved { background: #16a34a; }
        .mp-adj-dot-rejected { background: #dc2626; }

        .mp-sum-p  { color: #16a34a; font-weight: 700; }
        .mp-sum-a  { color: #dc2626; font-weight: 700; }
        .mp-sum-l  { color: #d97706; font-weight: 700; }
        .mp-sum-lv { color: #2563eb; font-weight: 700; }

        /* Repeat the header row on every printed page, and never split a
           single employee's row across a page break — same convention the
           Attendance Report's own PDF table already uses. */
        .mp-table thead { display: table-header-group; }
        .mp-table tr { page-break-inside: avoid; }
    </style>

    <div class="mp-legend">
        @foreach($legend as $code => $label)
            @php $bucket = array_search($code, \App\Services\AttendanceReportService::CODES, true); @endphp
            <span class="mp-legend-item"><span class="mp-code {{ $codeClass[$bucket] ?? '' }}">{{ $code }}</span> {{ $label }}</span>
        @endforeach
    </div>

    @if($employees->isEmpty())
        <p style="text-align:center; color:#8a9199; padding:30px 0;">No employees match the selected filters.</p>
    @else
        <table class="mp-table">
            <thead>
                <tr>
                    <th class="mp-emp-col">Employee</th>
                    @foreach($days as $date)
                        <th class="mp-day-col {{ \App\Services\WeekendCalendarService::isWeekend($date) ? 'mp-weekend-col' : '' }}">
                            <div class="mp-day-num">{{ $date->format('d') }}</div>
                            <div class="mp-day-dow">{{ $date->format('D') }}</div>
                        </th>
                    @endforeach
                    <th class="mp-sum-col">P</th>
                    <th class="mp-sum-col">A</th>
                    <th class="mp-sum-col">L</th>
                    <th class="mp-sum-col">LV</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                    @php $r = $reports[$employee->id] ?? null; @endphp
                    <tr>
                        <td class="mp-emp-col">
                            <div class="mp-emp-name">{{ $employee->full_name }} ({{ $employee->employee_code }})</div>
                            <div class="mp-emp-sub">{{ collect([$employee->designation?->name, $employee->department?->name])->filter()->implode(' · ') ?: '—' }}</div>
                        </td>

                        @if($r)
                            @foreach($r['days'] as $day)
                                @php $adjustment = $adjustments->get($employee->id . '|' . $day['date']->toDateString()); @endphp
                                <td class="mp-day-col {{ $day['is_weekend'] ? 'mp-weekend-col' : '' }}">
                                    <span class="mp-code {{ $codeClass[$day['bucket']] ?? '' }}">{{ $day['code'] }}</span>
                                    @if($adjustment)
                                        <span class="mp-adj-dot mp-adj-dot-{{ $adjustment->approval_status }}"></span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="mp-sum-col mp-sum-p">{{ $r['summary']['present'] }}</td>
                            <td class="mp-sum-col mp-sum-a">{{ $r['summary']['absent'] }}</td>
                            <td class="mp-sum-col mp-sum-l">{{ $r['summary']['late'] }}</td>
                            <td class="mp-sum-col mp-sum-lv">{{ $r['summary']['on_leave'] }}</td>
                        @else
                            <td colspan="{{ $dayCount + 4 }}" style="color:#8a9199;">No data</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-print-document>
