@extends('admin.layout.app', ['title' => 'Balance Sheet'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Balance Sheet</h2>
            <div class="sec-sub">Financial position as of a date, built from posted Journal Entries</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <input type="date" name="as_of_date" class="form-control form-control-sm w-auto" value="{{ $asOfDate }}" onchange="this.form.submit()" placeholder="As of">

            <select name="hide_zero" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Show All Accounts</option>
                <option value="1" {{ $hideZero ? 'selected' : '' }}>Hide Zero Balances</option>
            </select>

            @if($asOfDate || $hideZero)
                <a href="{{ route('admin.balance-sheet.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
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
                <div class="stat-lbl">Total Liabilities</div>
                <div class="stat-val">{{ number_format($totalLiabilities, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-scales-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Equity</div>
                <div class="stat-val">{{ number_format($totalEquity, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-pie-chart-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Status</div>
                <div class="stat-val">
                    @if($isBalanced)
                        <span class="badge bg-success">Balanced</span>
                    @else
                        <span class="badge bg-danger">Out of Balance</span>
                    @endif
                </div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row">

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Assets</div>
                    <div class="nx-card-sub">What the business owns{{ $asOfDate ? ' as of ' . \Carbon\Carbon::parse($asOfDate)->format('d M, Y') : '' }}</div>
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
                            @forelse($assetRows as $row)
                                <tr>
                                    <td>{{ $row['code'] }}</td>
                                    <td>
                                        <a href="{{ route('admin.general-ledger.index', array_filter(['account_id' => $row['account_id'], 'date_to' => $asOfDate])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td class="text-end {{ $row['amount'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No asset accounts found</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">Total Assets</th>
                                <th class="text-end">{{ number_format($totalAssets, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Liabilities &amp; Equity</div>
                    <div class="nx-card-sub">What the business owes and what the owners hold</div>
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
                            <tr class="text-muted">
                                <td colspan="3"><strong>Liabilities</strong></td>
                            </tr>
                            @forelse($liabilityRows as $row)
                                <tr>
                                    <td>{{ $row['code'] }}</td>
                                    <td>
                                        <a href="{{ route('admin.general-ledger.index', array_filter(['account_id' => $row['account_id'], 'date_to' => $asOfDate])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td class="text-end {{ $row['amount'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No liability accounts found</td>
                                </tr>
                            @endforelse
                            <tr>
                                <td colspan="2" class="text-end"><em>Total Liabilities</em></td>
                                <td class="text-end"><em>{{ number_format($totalLiabilities, 2) }}</em></td>
                            </tr>

                            <tr class="text-muted">
                                <td colspan="3"><strong>Equity</strong></td>
                            </tr>
                            @foreach($equityRows as $row)
                                <tr>
                                    <td>{{ $row['code'] }}</td>
                                    <td>
                                        <a href="{{ route('admin.general-ledger.index', array_filter(['account_id' => $row['account_id'], 'date_to' => $asOfDate])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td class="text-end {{ $row['amount'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td>-</td>
                                <td>
                                    Retained Earnings
                                    <div class="text-muted small">Cumulative revenue less expenses (computed, not a posted account)</div>
                                </td>
                                <td class="text-end {{ $retainedEarnings < 0 ? 'text-danger' : '' }}">{{ number_format($retainedEarnings, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end"><em>Total Equity</em></td>
                                <td class="text-end"><em>{{ number_format($totalEquity, 2) }}</em></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">Total Liabilities &amp; Equity</th>
                                <th class="text-end {{ $isBalanced ? '' : 'text-danger' }}">{{ number_format($totalLiabilitiesAndEquity, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

    @unless($isBalanced)
        <div class="nx-card">
            <div class="nx-card-body">
                <div class="text-danger">
                    <i class="ri-error-warning-line"></i>
                    <strong>Out of balance by {{ number_format($difference, 2) }}.</strong>
                    Total Assets ({{ number_format($totalAssets, 2) }}) does not equal Total Liabilities &amp; Equity ({{ number_format($totalLiabilitiesAndEquity, 2) }}).
                    Review the Trial Balance for unbalanced or misclassified postings.
                </div>
            </div>
        </div>
    @endunless

@endsection
