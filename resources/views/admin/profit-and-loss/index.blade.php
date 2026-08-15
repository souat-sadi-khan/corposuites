@extends('admin.layout.app', ['title' => 'Profit and Loss'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Profit and Loss</h2>
            <div class="sec-sub">Revenue less expenses for the selected period, built from posted Journal Entries</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <input type="date" name="date_from" class="form-control form-control-sm w-auto" value="{{ $dateFrom }}" onchange="this.form.submit()" placeholder="From">
            <input type="date" name="date_to" class="form-control form-control-sm w-auto" value="{{ $dateTo }}" onchange="this.form.submit()" placeholder="To">

            <select name="hide_zero" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Show All Accounts</option>
                <option value="1" {{ $hideZero ? 'selected' : '' }}>Hide Zero Amounts</option>
            </select>

            @if($dateFrom || $dateTo || $hideZero)
                <a href="{{ route('admin.profit-and-loss.index') }}" class="btn-nx-outline btn-sm">
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
    </div>

    <div class="twin-row">

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Revenue</div>
                    <div class="nx-card-sub">Income earned{{ $dateFrom || $dateTo ? ' in the selected period' : ' across all posted entries' }}</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenueRows as $row)
                                <tr>
                                    <td>{{ $row['code'] }}</td>
                                    <td>
                                        <a href="{{ route('admin.general-ledger.index', array_filter(['account_id' => $row['account_id'], 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td class="text-end {{ $row['amount'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No revenue accounts with activity</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($revenueRows->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">Total Revenue</th>
                                    <th class="text-end">{{ number_format($totalRevenue, 2) }}</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Expenses</div>
                    <div class="nx-card-sub">Costs incurred{{ $dateFrom || $dateTo ? ' in the selected period' : ' across all posted entries' }}</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenseRows as $row)
                                <tr>
                                    <td>{{ $row['code'] }}</td>
                                    <td>
                                        <a href="{{ route('admin.general-ledger.index', array_filter(['account_id' => $row['account_id'], 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td class="text-end {{ $row['amount'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No expense accounts with activity</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($expenseRows->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">Total Expenses</th>
                                    <th class="text-end">{{ number_format($totalExpense, 2) }}</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="nx-card">
        <div class="nx-card-body">
            <table class="ractivity-tbl">
                <tbody>
                    <tr>
                        <td>Total Revenue</td>
                        <td class="text-end">{{ number_format($totalRevenue, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Less: Total Expenses</td>
                        <td class="text-end">({{ number_format($totalExpense, 2) }})</td>
                    </tr>
                    <tr>
                        <th>{{ $isProfit ? 'Net Profit' : 'Net Loss' }}</th>
                        <th class="text-end {{ $isProfit ? 'text-success' : 'text-danger' }}">{{ number_format(abs($netProfit), 2) }}</th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

@endsection
