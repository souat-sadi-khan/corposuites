@extends('admin.layout.app', ['title' => 'Sales Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Sales Overview</h2>
            <div class="sec-sub">Aggregated snapshot across quotations, orders, invoices, POS, and returns</div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Quotations</div>
                <div class="stat-val">{{ number_format($totalQuotations) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-file-list-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Orders</div>
                <div class="stat-val">{{ number_format($totalOrders) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-shopping-bag-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Quotation to Order Conversion</div>
                <div class="stat-val">{{ $conversionRate }}%</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-exchange-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Invoiced Revenue</div>
                <div class="stat-val">{{ number_format($totalRevenue, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-bill-line"></i>
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
                <div class="stat-lbl">Total Invoices</div>
                <div class="stat-val">{{ number_format($totalInvoices) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-file-paper-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">POS Sales Revenue</div>
                <div class="stat-val">{{ number_format($totalPosSales, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-store-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Pending Returns</div>
                <div class="stat-val">{{ number_format($pendingReturns) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-arrow-go-back-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Orders by Status</div>
                    <div class="nx-card-sub">All sales orders grouped by their current status</div>
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
                    <div class="nx-card-title">Invoices by Status</div>
                    <div class="nx-card-sub">All sales invoices grouped by their current status</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="invoicesByStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Top Salespersons</div>
                <div class="nx-card-sub">Top 10 by non-cancelled order revenue (all time)</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Salesperson</th>
                            <th>Orders</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topSalespersons as $row)
                            <tr>
                                <td>{{ $row->admin->name ?? 'Unknown' }}</td>
                                <td>{{ $row->order_count }}</td>
                                <td>{{ number_format($row->total_sales, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No orders assigned to a salesperson yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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

        const invoicesByStatusLabels = @json($invoicesByStatus->keys());
        const invoicesByStatusData = @json($invoicesByStatus->values());

        new Chart(document.getElementById('invoicesByStatusChart'), {
            type: 'doughnut',
            data: {
                labels: invoicesByStatusLabels,
                datasets: [{
                    data: invoicesByStatusData,
                    backgroundColor: ['#16a34a', '#dc2626', '#f59e0b', '#0ea5e9', '#a855f7', '#6366f5']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
