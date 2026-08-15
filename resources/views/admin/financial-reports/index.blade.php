@extends('admin.layout.app', ['title' => 'Financial Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Financial Reports</h2>
            <div class="sec-sub">Headline figures from every Accounting statement, built from posted Journal Entries</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <input type="date" name="date_from" class="form-control form-control-sm w-auto" value="{{ $dateFrom }}" onchange="this.form.submit()" placeholder="From">
            <input type="date" name="date_to" class="form-control form-control-sm w-auto" value="{{ $dateTo }}" onchange="this.form.submit()" placeholder="To">

            @if($dateFrom || $dateTo)
                <a href="{{ route('admin.financial-reports.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Revenue</div>
                <div class="stat-val">{{ number_format($totalRevenue, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-arrow-up-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Expenses</div>
                <div class="stat-val">{{ number_format($totalExpense, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-arrow-down-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">{{ $isProfit ? 'Net Profit' : 'Net Loss' }}</div>
                <div class="stat-val {{ $isProfit ? 'text-success' : 'text-danger' }}">{{ number_format(abs($netProfit), 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-line-chart-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Profit Margin</div>
                <div class="stat-val">{{ $profitMargin === null ? '-' : number_format($profitMargin, 2) . '%' }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-percent-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Assets</div>
                <div class="stat-val">{{ number_format($totalAssets, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-briefcase-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Cash &amp; Bank</div>
                <div class="stat-val {{ $cashBalance < 0 ? 'text-danger' : '' }}">{{ number_format($cashBalance, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-safe-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Receivables Outstanding</div>
                <div class="stat-val">{{ number_format($receivable['total'], 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-user-received-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Payables Outstanding</div>
                <div class="stat-val">{{ number_format($payable['total'], 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-user-shared-line"></i>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Revenue vs Expenses by Month</div>
                <div class="nx-card-sub">{{ $dateFrom || $dateTo ? 'Across the selected range' : 'Last 6 months' }} — the one view no single statement provides</div>
            </div>
        </div>

        <div class="nx-card-body">
            @if($monthlyTrend->isEmpty())
                <div class="text-center text-muted py-4">No posted activity to chart</div>
            @else
                <canvas id="financialTrendChart" height="90"></canvas>
            @endif
        </div>
    </div>

    <div class="twin-row">

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Financial Position</div>
                    <div class="nx-card-sub">Balance sheet summary{{ $dateTo ? ' as of ' . \Carbon\Carbon::parse($dateTo)->format('d M, Y') : '' }}</div>
                </div>
            </div>

            <div class="nx-card-body">
                <table class="ractivity-tbl">
                    <tbody>
                        <tr>
                            <td>Total Assets</td>
                            <td class="text-end">{{ number_format($totalAssets, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Liabilities</td>
                            <td class="text-end">{{ number_format($totalLiabilities, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Retained Earnings <span class="text-muted small">(computed)</span></td>
                            <td class="text-end {{ $retainedEarnings < 0 ? 'text-danger' : '' }}">{{ number_format($retainedEarnings, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Equity</td>
                            <td class="text-end">{{ number_format($totalEquity, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Balance Check</th>
                            <th class="text-end">
                                @if($balanceSheetBalanced)
                                    <span class="badge bg-success">Balanced</span>
                                @else
                                    <span class="badge bg-danger">Out of Balance</span>
                                @endif
                            </th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Receivables &amp; Payables</div>
                    <div class="nx-card-sub">Money owed to and by the business, across all non-cancelled invoices</div>
                </div>
            </div>

            <div class="nx-card-body">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end">Overdue</th>
                            <th class="text-end">Invoices</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="{{ route('admin.accounts-receivable.index') }}">Accounts Receivable</a></td>
                            <td class="text-end">{{ number_format($receivable['total'], 2) }}</td>
                            <td class="text-end {{ $receivable['overdue_count'] > 0 ? 'text-danger' : '' }}">
                                {{ $receivable['overdue_count'] > 0 ? number_format($receivable['overdue_total'], 2) . ' (' . $receivable['overdue_count'] . ')' : '-' }}
                            </td>
                            <td class="text-end">{{ $receivable['invoice_count'] }}</td>
                        </tr>
                        <tr>
                            <td><a href="{{ route('admin.accounts-payable.index') }}">Accounts Payable</a></td>
                            <td class="text-end">{{ number_format($payable['total'], 2) }}</td>
                            <td class="text-end {{ $payable['overdue_count'] > 0 ? 'text-danger' : '' }}">
                                {{ $payable['overdue_count'] > 0 ? number_format($payable['overdue_total'], 2) . ' (' . $payable['overdue_count'] . ')' : '-' }}
                            </td>
                            <td class="text-end">{{ $payable['invoice_count'] }}</td>
                        </tr>
                        <tr>
                            <th>Net Position</th>
                            <th class="text-end {{ ($receivable['total'] - $payable['total']) < 0 ? 'text-danger' : '' }}">
                                {{ number_format($receivable['total'] - $payable['total'], 2) }}
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">All Financial Statements</div>
                <div class="nx-card-sub">
                    {{ $postedEntries }} posted journal {{ \Illuminate\Support\Str::plural('entry', $postedEntries) }} in this period
                    @if($draftEntries > 0)
                        &middot; <span class="text-danger">{{ $draftEntries }} draft {{ \Illuminate\Support\Str::plural('entry', $draftEntries) }} not counted in any figure above</span>
                    @endif
                    &middot; {{ $activeTaxRates }} active tax {{ \Illuminate\Support\Str::plural('rate', $activeTaxRates) }}
                </div>
            </div>
        </div>

        <div class="nx-card-body">
            @php
                $statements = [
                    ['route' => 'admin.trial-balance.index', 'icon' => 'ri-scales-3-line', 'title' => 'Trial Balance', 'desc' => 'Every account\'s debit/credit balance, with a reconciliation check'],
                    ['route' => 'admin.profit-and-loss.index', 'icon' => 'ri-line-chart-line', 'title' => 'Profit and Loss', 'desc' => 'Revenue less expenses over a period'],
                    ['route' => 'admin.balance-sheet.index', 'icon' => 'ri-scales-line', 'title' => 'Balance Sheet', 'desc' => 'Assets against liabilities and equity at a point in time'],
                    ['route' => 'admin.cash-flow.index', 'icon' => 'ri-exchange-dollar-line', 'title' => 'Cash Flow', 'desc' => 'Cash movements by operating, investing and financing activity'],
                    ['route' => 'admin.general-ledger.index', 'icon' => 'ri-file-chart-2-line', 'title' => 'General Ledger', 'desc' => 'Per-account running balance with full transaction detail'],
                    ['route' => 'admin.cash-book.index', 'icon' => 'ri-safe-2-line', 'title' => 'Cash Book', 'desc' => 'Receipts and payments across cash and bank accounts'],
                    ['route' => 'admin.accounts-receivable.index', 'icon' => 'ri-user-received-line', 'title' => 'Accounts Receivable', 'desc' => 'Outstanding balances per customer'],
                    ['route' => 'admin.accounts-payable.index', 'icon' => 'ri-user-shared-line', 'title' => 'Accounts Payable', 'desc' => 'Outstanding balances per vendor'],
                ];
            @endphp

            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <tbody>
                        @foreach($statements as $statement)
                            <tr>
                                <td style="width:36px;"><i class="{{ $statement['icon'] }}"></i></td>
                                <td>
                                    <a href="{{ route($statement['route'], array_filter(['date_from' => $dateFrom, 'date_to' => $dateTo])) }}"><strong>{{ $statement['title'] }}</strong></a>
                                    <div class="text-muted small">{{ $statement['desc'] }}</div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route($statement['route'], array_filter(['date_from' => $dateFrom, 'date_to' => $dateTo])) }}" class="btn-nx-outline btn-sm">
                                        Open <i class="ri-arrow-right-s-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @if($monthlyTrend->isNotEmpty())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
        <script>
            new Chart(document.getElementById('financialTrendChart'), {
                type: 'bar',
                data: {
                    labels: @json($monthlyTrend->pluck('label')),
                    datasets: [
                        {
                            label: 'Revenue',
                            data: @json($monthlyTrend->pluck('revenue')),
                            backgroundColor: '#22c55e'
                        },
                        {
                            label: 'Expenses',
                            data: @json($monthlyTrend->pluck('expense')),
                            backgroundColor: '#f59e0b'
                        },
                        {
                            label: 'Net',
                            type: 'line',
                            data: @json($monthlyTrend->pluck('net')),
                            borderColor: '#3b82f6',
                            backgroundColor: '#3b82f6',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        </script>
    @endif
@endpush
