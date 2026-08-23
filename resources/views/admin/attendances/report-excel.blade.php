@php
    // Same "Excel export = real HTML table served as .xls" approach as
    // monthly-excel.blade.php / documented on
    // App\Traits\ExportsHtmlSpreadsheet — one single <table> throughout.
    $periodLabel = $month
        ? \Carbon\Carbon::parse($month . '-01')->format('F Y')
        : $from->format('d M Y') . ' – ' . $to->format('d M Y');

    $companyName = get_settings('company_trading_name')
        ?: get_settings('company_legal_name')
        ?: get_settings('brand_name')
        ?: config('app.name');

    $totalCols = 12; // Employee/Code/Department/Designation/Present/Absent/Late/Half Day/Leave/Worked/Overtime/Missing Checkout
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Attendance Report</title>
</head>
<body style="font-family: Calibri, Arial, sans-serif; font-size: 12px;">
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse: collapse;">
    <tr><td colspan="{{ $totalCols }}" style="font-size:16px; font-weight:bold; border:none;">{{ $companyName }}</td></tr>
    <tr><td colspan="{{ $totalCols }}" style="font-size:13px; font-weight:bold; border:none;">Attendance Report</td></tr>
    <tr><td colspan="{{ $totalCols }}" style="border:none; color:#667085;">{{ $periodLabel }} &middot; {{ $employees->count() }} employee(s)</td></tr>
    @foreach($filterSummary as $label => $value)
        <tr><td colspan="{{ $totalCols }}" style="border:none; color:#667085;"><b>{{ $label }}:</b> {{ $value }}</td></tr>
    @endforeach
    <tr><td colspan="{{ $totalCols }}" style="border:none;">&nbsp;</td></tr>

    <tr style="background:#f5f6f8; font-weight:bold;">
        <td colspan="{{ $totalCols }}" style="border:none; font-size:12.5px;">Summary Totals</td>
    </tr>
    <tr style="background:#f5f6f8; font-weight:bold;">
        <td>Present</td><td>Absent</td><td>Late</td><td>On Leave</td><td>Half Day</td>
        <td colspan="2">Worked Hours</td><td colspan="2">Overtime</td><td colspan="3">Missing Checkouts</td>
    </tr>
    <tr>
        <td style="text-align:center; color:#16a34a; font-weight:bold;">{{ $totals['present'] }}</td>
        <td style="text-align:center; color:#dc2626; font-weight:bold;">{{ $totals['absent'] }}</td>
        <td style="text-align:center; color:#d97706; font-weight:bold;">{{ $totals['late'] }}</td>
        <td style="text-align:center; color:#2563eb; font-weight:bold;">{{ $totals['on_leave'] }}</td>
        <td style="text-align:center;">{{ $totals['half_day'] }}</td>
        <td colspan="2" style="text-align:center;">{{ $totals['worked_label'] }}</td>
        <td colspan="2" style="text-align:center;">{{ $totals['overtime_label'] }}</td>
        <td colspan="3" style="text-align:center; color:#dc2626; font-weight:bold;">{{ $totals['missing_checkouts'] }}</td>
    </tr>
    <tr><td colspan="{{ $totalCols }}" style="border:none;">&nbsp;</td></tr>

    <tr style="background:#f5f6f8; font-weight:bold;">
        <td>Employee</td>
        <td>Code</td>
        <td>Department</td>
        <td>Designation</td>
        <td>Present</td>
        <td>Absent</td>
        <td>Late</td>
        <td>Half Day</td>
        <td>Leave</td>
        <td>Worked</td>
        <td>Overtime</td>
        <td>Missing Checkout</td>
    </tr>

    @forelse($employees as $employee)
        @php $summary = $reports[$employee->id]['summary'] ?? null; @endphp
        <tr>
            <td>{{ $employee->full_name }}</td>
            <td>{{ $employee->employee_code }}</td>
            <td>{{ $employee->department?->name ?: '' }}</td>
            <td>{{ $employee->designation?->name ?: '' }}</td>
            @if($summary)
                <td style="text-align:center; color:#16a34a; font-weight:bold;">{{ $summary['present'] }}</td>
                <td style="text-align:center; color:#dc2626; font-weight:bold;">{{ $summary['absent'] }}</td>
                <td style="text-align:center; color:#d97706; font-weight:bold;">{{ $summary['late'] }}</td>
                <td style="text-align:center;">{{ $summary['half_day'] }}</td>
                <td style="text-align:center; color:#2563eb; font-weight:bold;">{{ $summary['on_leave'] }}</td>
                <td style="text-align:center;">{{ $summary['worked_label'] }}</td>
                <td style="text-align:center;">{{ $summary['overtime_label'] }}</td>
                <td style="text-align:center; {{ $summary['missing_checkouts'] > 0 ? 'color:#dc2626; font-weight:bold;' : '' }}">{{ $summary['missing_checkouts'] }}</td>
            @else
                <td colspan="8">No data</td>
            @endif
        </tr>
    @empty
        <tr><td colspan="{{ $totalCols }}" style="text-align:center; color:#8a9199;">No employees match the selected filters.</td></tr>
    @endforelse
</table>

<p style="color:#8a9199; font-size:10.5px;">
    Generated on {{ now()->format('d M Y, h:i A') }}{{ auth()->guard('admin')->check() ? ' by ' . auth()->guard('admin')->user()->name : '' }}.
</p>
</body>
</html>
