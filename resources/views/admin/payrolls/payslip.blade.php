@php
    $earningItems = $payroll->items->where('type', 'earning');
    $deductionItems = $payroll->items->where('type', 'deduction');
    $periodLabel = \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y');
@endphp

<x-print-document
    title="Payslip"
    :subtitle="'For the month of ' . $periodLabel"
    :meta="array_filter([
        'Employee' => $payroll->employee?->full_name,
        'Employee Code' => $payroll->employee?->employee_code,
        'Department' => $payroll->employee?->department?->name,
        'Designation' => $payroll->employee?->designation?->name,
        'Pay Period' => $periodLabel,
        'Pay Type' => $payroll->salaryStructure?->pay_type_label,
        'Payment Status' => ucfirst($payroll->payment_status),
        'Payment Date' => $payroll->payment_date?->format('d M, Y'),
    ])"
>
    <style>
        .payslip-columns {
            display: flex;
            gap: 18px;
            margin-bottom: 4px;
        }

        .payslip-col {
            flex: 1;
            min-width: 0;
        }

        .payslip-col h3 {
            font-size: 12.5px;
            margin: 0 0 6px;
            padding: 6px 10px;
            border-radius: 6px 6px 0 0;
        }

        .payslip-col.earnings h3 {
            background: #e7f6ec;
            color: #1e7e39;
        }

        .payslip-col.deductions h3 {
            background: #fdecea;
            color: #b3261e;
        }

        .payslip-col table {
            margin-top: 0;
        }

        .payslip-col td.amount,
        .payslip-col th.amount {
            text-align: right;
            white-space: nowrap;
        }

        .payslip-col tfoot td {
            font-weight: 700;
            background: #f7f8fa;
        }

        .payslip-net {
            margin-top: 18px;
            border: 1px solid #1f2430;
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f5f6f8;
        }

        .payslip-net .label {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .payslip-net .value {
            font-size: 20px;
            font-weight: 800;
        }

        .payslip-words {
            margin-top: 8px;
            font-size: 11.5px;
            color: #555;
            font-style: italic;
        }

        .payslip-signatures {
            margin-top: 48px;
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }

        .payslip-signature {
            flex: 1;
            text-align: center;
        }

        .payslip-signature .line {
            border-top: 1px solid #1f2430;
            margin-bottom: 6px;
            padding-top: 30px;
        }

        .payslip-signature .label {
            font-size: 11.5px;
            color: #555;
        }
    </style>

    <div class="payslip-columns">
        <div class="payslip-col earnings">
            <h3>Earnings</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Salary</td>
                        <td class="amount">{{ format_currency($payroll->basic_salary) }}</td>
                    </tr>
                    @foreach($earningItems as $item)
                        <tr>
                            <td>
                                {{ $item->salaryComponent?->name ?? 'Component' }}
                                @if($item->occurrence_count)
                                    <br><small>{{ $item->occurrence_count }} occurrence(s)</small>
                                @endif
                            </td>
                            <td class="amount">{{ format_currency($item->amount) }}</td>
                        </tr>
                    @endforeach
                    @if($payroll->overtime_amount > 0)
                        <tr>
                            <td>Overtime ({{ rtrim(rtrim(number_format($payroll->overtime_hours, 2), '0'), '.') }}h)</td>
                            <td class="amount">{{ format_currency($payroll->overtime_amount) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total Earnings</td>
                        <td class="amount">{{ format_currency($payroll->total_earnings) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="payslip-col deductions">
            <h3>Deductions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deductionItems as $item)
                        <tr>
                            <td>
                                {{ $item->salaryComponent?->name ?? 'Component' }}
                                @if($item->occurrence_count)
                                    <br><small>{{ $item->occurrence_count }} occurrence(s)</small>
                                @endif
                            </td>
                            <td class="amount">{{ format_currency($item->amount) }}</td>
                        </tr>
                    @endforeach
                    @if($unpaidLeaveDeduction > 0)
                        <tr>
                            <td>Unpaid Leave Deduction</td>
                            <td class="amount">{{ format_currency($unpaidLeaveDeduction) }}</td>
                        </tr>
                    @endif
                    @if($payroll->attendance_deduction > 0)
                        <tr>
                            <td>Attendance Deduction (Late / Early Leave / Absent)</td>
                            <td class="amount">{{ format_currency($payroll->attendance_deduction) }}</td>
                        </tr>
                    @endif
                    @foreach($payroll->loanDeductions as $loanDeduction)
                        <tr>
                            <td>
                                Loan Installment
                                @if($loanDeduction->employeeLoan)
                                    <br><small>{{ format_currency($loanDeduction->employeeLoan->remaining_balance) }} remaining</small>
                                @endif
                            </td>
                            <td class="amount">{{ format_currency($loanDeduction->amount) }}</td>
                        </tr>
                    @endforeach
                    @if($deductionItems->isEmpty() && $unpaidLeaveDeduction <= 0 && $payroll->attendance_deduction <= 0 && $payroll->loanDeductions->isEmpty())
                        <tr>
                            <td colspan="2" style="text-align:center; color:#8a9199;">No deductions this period</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total Deductions</td>
                        <td class="amount">{{ format_currency($payroll->total_deductions) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="payslip-net">
        <div class="label">Net Salary</div>
        <div class="value">{{ format_currency($payroll->net_salary) }}</div>
    </div>
    <div class="payslip-words">
        Amount in words: {{ amount_in_words($payroll->net_salary) }} {{ get_settings('currency', 'USD') }} Only.
    </div>

    <div class="payslip-signatures">
        <div class="payslip-signature">
            <div class="line"></div>
            <div class="label">Employee Signature</div>
        </div>
        <div class="payslip-signature">
            <div class="line"></div>
            <div class="label">Authorized Signatory</div>
        </div>
    </div>
</x-print-document>
