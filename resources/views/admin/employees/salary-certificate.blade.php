@php
    $issueDateFormatted = \Carbon\Carbon::parse($issueDate)->format('d M, Y');
@endphp

<x-print-document
    title="Salary Certificate"
    :subtitle="'Issued on ' . $issueDateFormatted"
    :meta="array_filter([
        'Employee' => $employee->full_name,
        'Employee Code' => $employee->employee_code,
        'Department' => $employee->department?->name,
        'Designation' => $employee->designation?->name,
        'Date of Joining' => $employee->date_of_joining?->format('d M, Y'),
    ])"
>
    <style>
        .cert-heading {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .6px;
            margin: 6px 0 20px;
            text-decoration: underline;
        }

        .cert-body p {
            font-size: 13px;
            line-height: 1.8;
            margin: 0 0 14px;
            text-align: justify;
        }

        .cert-body table {
            margin: 6px 0 16px;
        }

        .cert-body td.amount,
        .cert-body th.amount {
            text-align: right;
            white-space: nowrap;
        }

        .cert-body tfoot td {
            font-weight: 700;
            background: #f7f8fa;
        }

        .cert-none {
            padding: 24px;
            text-align: center;
            border: 1px dashed #c9ccd3;
            border-radius: 8px;
            color: #667085;
        }

        .cert-signatures {
            margin-top: 56px;
            display: flex;
            justify-content: flex-end;
        }

        .cert-signature {
            text-align: center;
            width: 220px;
        }

        .cert-signature .line {
            border-top: 1px solid #1f2430;
            margin-bottom: 6px;
            padding-top: 40px;
        }

        .cert-signature .label {
            font-size: 11.5px;
            color: #555;
        }
    </style>

    @if(! $structure)
        <div class="cert-none">
            <strong>No active Salary Structure found for this employee.</strong>
            <p style="margin-top:8px; font-size:12.5px;">
                A salary certificate is generated from the employee's current active Salary Structure.
                Create one first, then try again.
            </p>
        </div>
    @else
        @php
            $companyName = get_settings('company_trading_name')
                ?: get_settings('company_legal_name')
                ?: get_settings('brand_name')
                ?: config('app.name');

            $earningItems = $structure->items->filter(fn ($item) => $item->salaryComponent && $item->salaryComponent->type === 'earning');
            $deductionItems = $structure->items->filter(fn ($item) => $item->salaryComponent && $item->salaryComponent->type === 'deduction');

            $rateLine = match ($structure->pay_type) {
                'daily' => 'a daily wage rate of ' . format_currency($structure->basic_salary) . ' per day worked',
                'commission' => 'a commission rate of ' . rtrim(rtrim(number_format($structure->basic_salary, 2), '0'), '.') . '% of sales generated',
                default => 'a gross monthly salary of ' . format_currency($structure->gross_salary) . ' (' . amount_in_words($structure->gross_salary) . ' ' . get_settings('currency', 'USD') . ' Only)',
            };
        @endphp

        <div class="cert-heading">To Whomsoever It May Concern</div>

        <div class="cert-body">
            <p>
                This is to certify that <strong>{{ $employee->full_name }}</strong> (Employee Code: <strong>{{ $employee->employee_code }}</strong>)
                is a bona fide employee of <strong>{{ $companyName }}</strong>, having joined the organization on
                <strong>{{ $employee->date_of_joining?->format('d M, Y') ?? 'N/A' }}</strong>, and currently holds the position of
                <strong>{{ $employee->designation?->name ?? 'N/A' }}</strong>
                @if($employee->department?->name)
                    in the <strong>{{ $employee->department->name }}</strong> department
                @endif
                .
            </p>

            <p>
                As per our records, the employee is currently drawing {{ $rateLine }}, effective from
                <strong>{{ $structure->effective_date?->format('d M, Y') }}</strong>.
            </p>

            @if($structure->pay_type === 'monthly' && ($earningItems->count() || $deductionItems->count()))
                <table>
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th class="amount">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td class="amount">{{ format_currency($structure->basic_salary) }}</td>
                        </tr>
                        @foreach($earningItems as $item)
                            <tr>
                                <td>{{ $item->salaryComponent->name }}</td>
                                <td class="amount">{{ format_currency($item->amount) }}</td>
                            </tr>
                        @endforeach
                        @foreach($deductionItems as $item)
                            <tr>
                                <td>{{ $item->salaryComponent->name }} (Deduction)</td>
                                <td class="amount">-{{ format_currency($item->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Gross Salary</td>
                            <td class="amount">{{ format_currency($structure->gross_salary) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            <p>
                This certificate is issued at the request of the employee for {{ $purpose }}, and should not be construed
                as a guarantee of continued employment or future compensation.
            </p>
        </div>

        <div class="cert-signatures">
            <div class="cert-signature">
                <div class="line"></div>
                <div class="label">Authorized Signatory</div>
            </div>
        </div>
    @endif
</x-print-document>
