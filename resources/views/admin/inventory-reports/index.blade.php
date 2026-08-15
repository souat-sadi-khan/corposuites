@extends('admin.layout.app', ['title' => 'Inventory Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Inventory Overview</h2>
            <div class="sec-sub">Aggregated snapshot across warehouses, stock movements, batches, serials, and stock health</div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Warehouses</div>
                <div class="stat-val">{{ number_format($totalWarehouses) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-building-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Active Warehouses</div>
                <div class="stat-val">{{ number_format($activeWarehouses) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Products Tracked</div>
                <div class="stat-val">{{ number_format($totalProductsTracked) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-box-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Inventory Value</div>
                <div class="stat-val">{{ number_format($totalInventoryValue, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Low Stock Alerts</div>
                <div class="stat-val">{{ number_format($totalLowStockAlerts) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-alarm-warning-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Out of Stock</div>
                <div class="stat-val">{{ number_format($outOfStockCount) }}</div>
            </div>
            <div class="stat-icon-wrap si-red">
                <i class="ri-close-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Batches Tracked</div>
                <div class="stat-val">{{ number_format($totalBatches) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-barcode-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Expired Batches</div>
                <div class="stat-val">{{ number_format($expiredBatches) }}</div>
            </div>
            <div class="stat-icon-wrap si-red">
                <i class="ri-time-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Serial Numbers Tracked</div>
                <div class="stat-val">{{ number_format($totalSerials) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-qr-code-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Movement Documents by Type</div>
                    <div class="nx-card-sub">Total documents recorded per movement type ({{ number_format($totalMovementDocuments) }} total)</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="documentTypeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Serial Numbers by Status</div>
                    <div class="nx-card-sub">Tracked serialized units grouped by current state</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="serialStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Top Low Stock Items</div>
                <div class="nx-card-sub">Up to 10 product/warehouse combinations with the largest shortfall against their configured Reorder Level</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th class="text-end">Current Balance</th>
                            <th class="text-end">Reorder Level</th>
                            <th class="text-end">Shortfall</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topLowStockAlerts as $row)
                            <tr>
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['warehouse'] }}</td>
                                <td class="text-end {{ $row['balance'] <= 0 ? 'text-danger' : '' }}">{{ number_format($row['balance'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['reorder_level'], 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($row['shortfall'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No low stock alerts — all tracked stock is above its reorder level</td>
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
        const documentTypeLabels = @json(array_keys($documentCounts));
        const documentTypeData = @json(array_values($documentCounts));

        new Chart(document.getElementById('documentTypeChart'), {
            type: 'bar',
            data: {
                labels: documentTypeLabels,
                datasets: [{
                    label: 'Documents',
                    data: documentTypeData,
                    backgroundColor: 'rgba(101,103,245,.7)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        const serialStatusLabels = @json($serialsByStatus->keys());
        const serialStatusData = @json($serialsByStatus->values());

        new Chart(document.getElementById('serialStatusChart'), {
            type: 'doughnut',
            data: {
                labels: serialStatusLabels,
                datasets: [{
                    data: serialStatusData,
                    backgroundColor: ['#16a34a', '#0ea5e9', '#dc2626', '#f59e0b', '#a855f7']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
