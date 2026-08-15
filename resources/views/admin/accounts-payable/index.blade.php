@extends('admin.layout.app', ['title' => 'Accounts Payable'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Accounts Payable</h2>
            <div class="sec-sub">Per-vendor outstanding balance built from Purchase Invoices</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="vendor_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Vendors (Summary)</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ (string) $vendorId === (string) $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" class="form-control form-control-sm w-auto" value="{{ $dateFrom }}" onchange="this.form.submit()" placeholder="From">
            <input type="date" name="date_to" class="form-control form-control-sm w-auto" value="{{ $dateTo }}" onchange="this.form.submit()" placeholder="To">

            @if($vendorId || $dateFrom || $dateTo)
                <a href="{{ route('admin.accounts-payable.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    @if($selectedVendor && $ledger)

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
                    <i class="ri-arrow-up-circle-line"></i>
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
                    <div class="nx-card-title">{{ $selectedVendor->name }}</div>
                    <div class="nx-card-sub">{{ $selectedVendor->vendor_code }}</div>
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
                                    <td colspan="7" class="text-center text-muted">No invoices for this vendor in the selected period</td>
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
                    <div class="stat-lbl">Total Outstanding (All Vendors)</div>
                    <div class="stat-val">{{ number_format($totalOutstanding, 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-amber">
                    <i class="ri-truck-line"></i>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Vendor Balances Overview</div>
                    <div class="nx-card-sub">Invoiced/paid/outstanding per vendor{{ $dateTo ? ' as of ' . \Carbon\Carbon::parse($dateTo)->format('d M, Y') : '' }} — select a vendor above to view their full invoice list</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Vendor</th>
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
                                        <a href="{{ route('admin.accounts-payable.index', array_filter(['vendor_id' => $row['vendor_id'], 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td>{{ $row['vendor_code'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_invoiced'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['total_paid'], 2) }}</td>
                                    <td class="text-end {{ $row['balance_due'] > 0 ? 'text-danger' : '' }}">{{ number_format($row['balance_due'], 2) }}</td>
                                    <td class="text-end">{{ $row['overdue_count'] > 0 ? $row['overdue_count'] : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No vendors with invoice activity found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @endif

@endsection
