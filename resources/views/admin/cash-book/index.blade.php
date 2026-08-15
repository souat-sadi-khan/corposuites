@extends('admin.layout.app', ['title' => 'Cash Book'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Cash Book</h2>
            <div class="sec-sub">Receipts and payments running balance for Cash/Bank accounts, built from posted Journal Entries</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="account_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Cash/Bank Accounts (Summary)</option>
                @foreach($cashAccounts as $account)
                    <option value="{{ $account->id }}" {{ (string) $accountId === (string) $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" class="form-control form-control-sm w-auto" value="{{ $dateFrom }}" onchange="this.form.submit()" placeholder="From">
            <input type="date" name="date_to" class="form-control form-control-sm w-auto" value="{{ $dateTo }}" onchange="this.form.submit()" placeholder="To">

            @if($accountId || $dateFrom || $dateTo)
                <a href="{{ route('admin.cash-book.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    @if($cashAccounts->isEmpty())
        <div class="nx-card">
            <div class="nx-card-body">
                <p class="text-muted mb-0">No Cash/Bank accounts found. Create an <a href="{{ route('admin.account-types.index') }}">Account Type</a> named "Cash" or "Bank" and assign it to the relevant <a href="{{ route('admin.chart-of-accounts.index') }}">Chart of Accounts</a> entries to have them appear here.</p>
            </div>
        </div>
    @elseif($selectedAccount && $book)

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Opening Balance</div>
                    <div class="stat-val">{{ number_format($book['opening_balance'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-blue">
                    <i class="ri-flag-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Total Receipts</div>
                    <div class="stat-val">{{ number_format($book['total_receipts'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-green">
                    <i class="ri-arrow-down-circle-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Total Payments</div>
                    <div class="stat-val">{{ number_format($book['total_payments'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-amber">
                    <i class="ri-arrow-up-circle-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Closing Balance</div>
                    <div class="stat-val">{{ number_format($book['closing_balance'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-purple">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">{{ $selectedAccount->code }} - {{ $selectedAccount->name }}</div>
                    <div class="nx-card-sub">{{ $selectedAccount->accountType->name ?? 'Cash/Bank' }} account</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Entry #</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-end">Receipt</th>
                                <th class="text-end">Payment</th>
                                <th class="text-end">Running Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-muted">
                                <td colspan="6">Opening Balance</td>
                                <td class="text-end">{{ number_format($book['opening_balance'], 2) }}</td>
                            </tr>
                            @forelse($book['lines'] as $line)
                                <tr>
                                    <td>{{ $line['entry_date']->format('d M, Y') }}</td>
                                    <td>{{ $line['entry_number'] }}</td>
                                    <td>{{ $line['reference'] ?: '-' }}</td>
                                    <td>{{ $line['description'] ?: '-' }}</td>
                                    <td class="text-end">{{ $line['receipt'] > 0 ? number_format($line['receipt'], 2) : '-' }}</td>
                                    <td class="text-end">{{ $line['payment'] > 0 ? number_format($line['payment'], 2) : '-' }}</td>
                                    <td class="text-end">{{ number_format($line['running_balance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No posted transactions for this account in the selected period</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @else

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Total Cash &amp; Bank Balance</div>
                    <div class="stat-val">{{ number_format($totalCashBalance, 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-green">
                    <i class="ri-safe-2-line"></i>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Cash/Bank Accounts Overview</div>
                    <div class="nx-card-sub">Closing balance per Cash/Bank account{{ $dateTo ? ' as of ' . \Carbon\Carbon::parse($dateTo)->format('d M, Y') : '' }} — select an account above to view its full cash book</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account</th>
                                <th>Type</th>
                                <th class="text-end">Total Receipts</th>
                                <th class="text-end">Total Payments</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary as $row)
                                <tr>
                                    <td>{{ $row['code'] }}</td>
                                    <td>
                                        <a href="{{ route('admin.cash-book.index', array_filter(['account_id' => $row['account_id'], 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $row['account_type_name'] }}</span></td>
                                    <td class="text-end">{{ number_format($row['total_receipts'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['total_payments'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No Cash/Bank accounts found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @endif

@endsection
