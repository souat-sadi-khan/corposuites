@extends('admin.layout.app', ['title' => 'Trial Balance'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Trial Balance</h2>
            <div class="sec-sub">All-accounts debit/credit snapshot built from posted Journal Entries</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <input type="date" name="as_of_date" class="form-control form-control-sm w-auto" value="{{ $asOfDate }}" onchange="this.form.submit()" placeholder="As of">

            <select name="account_type" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Account Types</option>
                @foreach(\App\Models\ChartOfAccount::ACCOUNT_TYPES as $type)
                    <option value="{{ $type }}" {{ $accountType === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                @endforeach
            </select>

            <select name="hide_zero" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Show All Accounts</option>
                <option value="1" {{ $hideZero ? 'selected' : '' }}>Hide Zero Balances</option>
            </select>

            @if($asOfDate || $accountType || $hideZero)
                <a href="{{ route('admin.trial-balance.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Debit</div>
                <div class="stat-val">{{ number_format($totalDebit, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-arrow-down-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Credit</div>
                <div class="stat-val">{{ number_format($totalCredit, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-arrow-up-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Difference</div>
                <div class="stat-val {{ $isBalanced ? '' : 'text-danger' }}">{{ number_format($difference, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-scales-3-line"></i>
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

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Trial Balance</div>
                <div class="nx-card-sub">Closing balance per postable account{{ $asOfDate ? ' as of ' . \Carbon\Carbon::parse($asOfDate)->format('d M, Y') : '' }} &middot; only posted journal entries are counted</div>
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
                            <th>Normal</th>
                            <th class="text-end">Total Debit</th>
                            <th class="text-end">Total Credit</th>
                            <th class="text-end">Debit Balance</th>
                            <th class="text-end">Credit Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td>
                                <td>
                                    <a href="{{ route('admin.general-ledger.index', array_filter(['account_id' => $row['account_id'], 'date_to' => $asOfDate])) }}">{{ $row['name'] }}</a>
                                </td>
                                <td><span class="badge bg-secondary">{{ ucfirst($row['account_type']) }}</span></td>
                                <td class="text-muted">{{ ucfirst($row['normal_balance']) }}</td>
                                <td class="text-end">{{ $row['total_debit'] > 0 ? number_format($row['total_debit'], 2) : '-' }}</td>
                                <td class="text-end">{{ $row['total_credit'] > 0 ? number_format($row['total_credit'], 2) : '-' }}</td>
                                <td class="text-end">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                                <td class="text-end">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No postable accounts found for the selected filters</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">Totals</th>
                                <th class="text-end {{ $isBalanced ? '' : 'text-danger' }}">{{ number_format($totalDebit, 2) }}</th>
                                <th class="text-end {{ $isBalanced ? '' : 'text-danger' }}">{{ number_format($totalCredit, 2) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

@endsection
