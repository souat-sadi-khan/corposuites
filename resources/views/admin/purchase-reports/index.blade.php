@extends('admin.layout.app', ['title' => 'Purchase Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Purchase Overview</h2>
            <div class="sec-sub">Aggregated snapshot across vendors, requests, orders, invoices, and returns</div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Vendors</div>
                <div class="stat-val">{{ number_format($totalVendors) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-building-4-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Active Vendors</div>
                <div class="stat-val">{{ number_format($activeVendors) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Request Approval Rate</div>
                <div class="stat-val">{{ $approvalRate }}%</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-file-list-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Orders</div>
                <div class="stat-val">{{ number_format($totalOrders) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-shopping-cart-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Purchase Spend</div>
                <div class="stat-val">{{ number_format($totalSpend, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Outstanding Balance</div>
                <div class="stat-val">{{ number_format($outstandingBalance, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-alarm-warning-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Invoices with Discrepancy</div>
                <div class="stat-val">{{ number_format($discrepancyInvoices) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-error-warning-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Pending Returns</div>
                <div class="stat-val">{{ number_format($pendingReturns) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-arrow-go-back-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Orders by Status</div>
                    <div class="nx-card-sub">All purchase orders grouped by their current status</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="ordersByStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Invoices by Match Status</div>
                    <div class="nx-card-sub">All purchase invoices grouped by 3-way match result</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="invoicesByMatchStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Top Vendors</div>
                <div class="nx-card-sub">Top 10 by non-cancelled purchase order spend (all time), with average performance rating where available</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Orders</th>
                            <th>Total Spend</th>
                            <th>Avg. Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topVendors as $row)
                            <tr>
                                <td>{{ $row->vendor->name ?? 'Unknown' }}</td>
                                <td>{{ $row->order_count }}</td>
                                <td>{{ number_format($row->total_spend, 2) }}</td>
                                <td>{{ isset($vendorRatings[$row->vendor_id]) ? number_format($vendorRatings[$row->vendor_id], 1) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No purchase orders recorded yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="nx-card mt-3">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Debit Notes Issued</div>
                <div class="nx-card-sub">Total value of non-cancelled debit notes against vendors</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div class="stat-val">{{ number_format($totalDebitAmount, 2) }}</div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script>
        const ordersByStatusLabels = @json($ordersByStatus->keys());
        const ordersByStatusData = @json($ordersByStatus->values());

        new Chart(document.getElementById('ordersByStatusChart'), {
            type: 'bar',
            data: {
                labels: ordersByStatusLabels,
                datasets: [{
                    label: 'Orders',
                    data: ordersByStatusData,
                    backgroundColor: 'rgba(101,103,245,.7)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        const invoicesByMatchStatusLabels = @json($invoicesByMatchStatus->keys());
        const invoicesByMatchStatusData = @json($invoicesByMatchStatus->values());

        new Chart(document.getElementById('invoicesByMatchStatusChart'), {
            type: 'doughnut',
            data: {
                labels: invoicesByMatchStatusLabels,
                datasets: [{
                    data: invoicesByMatchStatusData,
                    backgroundColor: ['#16a34a', '#dc2626', '#94a3b8', '#f59e0b', '#0ea5e9', '#a855f7']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
