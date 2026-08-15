@extends('admin.layout.app', ['title' => 'Accounts Receivable'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Accounts Receivable</h2>
            <div class="sec-sub">Per-customer outstanding balance built from Sales Invoices</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="customer_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Customers (Summary)</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ (string) $customerId === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" class="form-control form-control-sm w-auto" value="{{ $dateFrom }}" onchange="this.form.submit()" placeholder="From">
            <input type="date" name="date_to" class="form-control form-control-sm w-auto" value="{{ $dateTo }}" onchange="this.form.submit()" placeholder="To">

            @if($customerId || $dateFrom || $dateTo)
                <a href="{{ route('admin.accounts-receivable.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    @if($selectedCustomer && $ledger)

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Total Invoiced</div>
                    <div class="stat-val">{{ number_format($ledger['total_invoiced'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-blue">
                    <i class="ri-file-list-3-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Total Paid</div>
                    <div class="stat-val">{{ number_format($ledger['total_paid'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-green">
                    <i class="ri-arrow-down-circle-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Outstanding Balance</div>
                    <div class="stat-val">{{ number_format($ledger['total_outstanding'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-amber">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Overdue Invoices</div>
                    <div class="stat-val">{{ $ledger['overdue_count'] }}</div>
                </div>
                <div class="stat-icon-wrap si-purple">
                    <i class="ri-alarm-warning-line"></i>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">{{ $selectedCustomer->name }}</div>
                    <div class="nx-card-sub">{{ $selectedCustomer->customer_code }}</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th>Invoice #</th>
                                <th>Status</th>
                                <th class="text-end">Invoiced</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ledger['lines'] as $line)
                                <tr class="{{ $line['is_overdue'] ? 'text-danger' : '' }}">
                                    <td>{{ $line['invoice_date']->format('d M, Y') }}</td>
                                    <td>{{ $line['due_date'] ? $line['due_date']->format('d M, Y') : '-' }}{{ $line['is_overdue'] ? ' (Overdue)' : '' }}</td>
                                    <td>{{ $line['invoice_number'] }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $line['invoice_status'])) }}</span></td>
                                    <td class="text-end">{{ number_format($line['grand_total'], 2) }}</td>
                                    <td class="text-end">{{ number_format($line['amount_paid'], 2) }}</td>
                                    <td class="text-end">{{ number_format($line['balance_due'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No invoices for this customer in the selected period</td>
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
                    <div class="stat-lbl">Total Outstanding (All Customers)</div>
                    <div class="stat-val">{{ number_format($totalOutstanding, 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-amber">
                    <i class="ri-user-received-2-line"></i>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Customer Balances Overview</div>
                    <div class="nx-card-sub">Invoiced/paid/outstanding per customer{{ $dateTo ? ' as of ' . \Carbon\Carbon::parse($dateTo)->format('d M, Y') : '' }} — select a customer above to view their full invoice list</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Code</th>
                                <th class="text-end">Total Invoiced</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Balance Due</th>
                                <th class="text-end">Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.accounts-receivable.index', array_filter(['customer_id' => $row['customer_id'], 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td>{{ $row['customer_code'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_invoiced'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['total_paid'], 2) }}</td>
                                    <td class="text-end {{ $row['balance_due'] > 0 ? 'text-danger' : '' }}">{{ number_format($row['balance_due'], 2) }}</td>
                                    <td class="text-end">{{ $row['overdue_count'] > 0 ? $row['overdue_count'] : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No customers with invoice activity found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @endif

@endsection
