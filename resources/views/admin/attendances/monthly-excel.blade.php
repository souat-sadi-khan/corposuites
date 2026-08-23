@php
    // Same "Excel export = real HTML table served as .xls" approach
    // documented on App\Traits\ExportsHtmlSpreadsheet — kept as ONE single
    // <table> (meta rows + a blank spacer row + the header row + the data
    // rows) rather than plain <div>s, since Excel's own "open an HTML file"
    // import reliably reads table cells but can drop or misplace anything
    // outside a <table>.
    //
    // Same bucket->CSS-class / letter-code mapping the on-screen sheet and
    // its PDF export both already use — reused as-is so these badges mean
    // exactly the same thing everywhere.
    $codeColors = [
        'present' => ['bg' => '#dcfce7', 'fg' => '#16a34a'],
        'absent' => ['bg' => '#fee2e2', 'fg' => '#dc2626'],
        'late' => ['bg' => '#fef3c7', 'fg' => '#d97706'],
        'half_day' => ['bg' => '#fef3c7', 'fg' => '#d97706'],
        'early_leave' => ['bg' => '#fef3c7', 'fg' => '#d97706'],
        'on_leave' => ['bg' => '#dbeafe', 'fg' => '#2563eb'],
        'holiday' => ['bg' => '#f3e8ff', 'fg' => '#9333ea'],
        'weekly_off' => ['bg' => '#f1f5f9', 'fg' => '#64748b'],
        'pending' => ['bg' => '#ffffff', 'fg' => '#b0b6bd'],
    ];

    $days = collect($from->toPeriod($to));
    $totalCols = 4 + $days->count() + 4; // Employee/Code/Department/Designation + one per day + P/A/L/LV

    $companyName = get_settings('company_trading_name')
        ?: get_settings('company_legal_name')
        ?: get_settings('brand_name')
        ?: config('app.name');
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Monthly Attendance Sheet</title>
</head>
<body style="font-family: Calibri, Arial, sans-serif; font-size: 12px;">
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse: collapse;">
    <tr><td colspan="{{ $totalCols }}" style="font-size:16px; font-weight:bold; border:none;">{{ $companyName }}</td></tr>
    <tr><td colspan="{{ $totalCols }}" style="font-size:13px; font-weight:bold; border:none;">Monthly Attendance Sheet</td></tr>
    <tr><td colspan="{{ $totalCols }}" style="border:none; color:#667085;">{{ $from->format('F Y') }} &middot; {{ $employees->count() }} employee(s)</td></tr>
    @foreach($filterSummary as $label => $value)
        <tr><td colspan="{{ $totalCols }}" style="border:none; color:#667085;"><b>{{ $label }}:</b> {{ $value }}</td></tr>
    @endforeach
    <tr><td colspan="{{ $totalCols }}" style="border:none;">&nbsp;</td></tr>

    <tr style="background:#f5f6f8; font-weight:bold;">
        <td>Employee</td>
        <td>Code</td>
        <td>Department</td>
        <td>Designation</td>
        @foreach($days as $date)
            <td style="{{ \App\Services\WeekendCalendarService::isWeekend($date) ? 'background:#eef1f5;' : '' }} text-align:center;">{{ $date->format('d') }}<br>{{ $date->format('D') }}</td>
        @endforeach
        <td>P</td>
        <td>A</td>
        <td>L</td>
        <td>LV</td>
    </tr>

    @forelse($employees as $employee)
        @php $r = $reports[$employee->id] ?? null; @endphp
        <tr>
            <td>{{ $employee->full_name }}</td>
            <td>{{ $employee->employee_code }}</td>
            <td>{{ $employee->department?->name ?: '' }}</td>
            <td>{{ $employee->designation?->name ?: '' }}</td>

            @if($r)
                @foreach($r['days'] as $day)
                    @php
                        $colors = $codeColors[$day['bucket']] ?? ['bg' => '#ffffff', 'fg' => '#000000'];
                        $adjustment = $adjustments->get($employee->id . '|' . $day['date']->toDateString());
                    @endphp
                    <td style="text-align:center; background:{{ $colors['bg'] }}; color:{{ $colors['fg'] }}; font-weight:bold;">
                        {{ $day['code'] }}@if($adjustment) *@endif
                    </td>
                @endforeach
                <td style="text-align:center; color:#16a34a; font-weight:bold;">{{ $r['summary']['present'] }}</td>
                <td style="text-align:center; color:#dc2626; font-weight:bold;">{{ $r['summary']['absent'] }}</td>
                <td style="text-align:center; color:#d97706; font-weight:bold;">{{ $r['summary']['late'] }}</td>
                <td style="text-align:center; color:#2563eb; font-weight:bold;">{{ $r['summary']['on_leave'] }}</td>
            @else
                <td colspan="{{ $days->count() + 4 }}">No data</td>
            @endif
        </tr>
    @empty
        <tr><td colspan="{{ $totalCols }}" style="text-align:center; color:#8a9199;">No employees match the selected filters.</td></tr>
    @endforelse
</table>

<p style="color:#8a9199; font-size:10.5px;">
    * = has a pending/approved/rejected attendance adjustment for that day.
    Generated on {{ now()->format('d M Y, h:i A') }}{{ auth()->guard('admin')->check() ? ' by ' . auth()->guard('admin')->user()->name : '' }}.
</p>
</body>
</html>
