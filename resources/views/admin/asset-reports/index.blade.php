@extends('admin.layout.app', ['title' => 'Asset Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Asset Reports</h2>
            <div class="sec-sub">Headline figures from across Asset Management</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="asset_category_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>

            <input type="date" name="as_of_date" class="form-control form-control-sm w-auto" value="{{ $asOfDate }}" onchange="this.form.submit()">

            @if($categoryId)
                <a href="{{ route('admin.asset-reports.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Assets</div>
                <div class="stat-val">{{ $totals['assets'] }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-hard-drive-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">In Use</div>
                <div class="stat-val">{{ $totals['in_use'] }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-user-shared-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Under Maintenance</div>
                <div class="stat-val {{ $totals['under_maintenance'] > 0 ? 'text-danger' : '' }}">{{ $totals['under_maintenance'] }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-tools-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Disposed</div>
                <div class="stat-val">{{ $totals['disposed'] }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-delete-bin-6-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Cost</div>
                <div class="stat-val">{{ number_format($totals['cost'], 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-money-dollar-box-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Net Book Value</div>
                <div class="stat-val">{{ number_format($totals['book_value'], 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-scales-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Accumulated Depreciation</div>
                <div class="stat-val">{{ number_format($totals['accumulated_depreciation'], 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-line-chart-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Assets Out With Staff</div>
                <div class="stat-val">
                    {{ $assignments['out'] }}
                    @if($assignments['overdue'] > 0)
                        <small class="text-danger">/ {{ $assignments['overdue'] }} overdue</small>
                    @endif
                </div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-team-line"></i>
            </div>
        </div>
    </div>

    @if($totals['missing_purchase'] > 0)
        <div class="nx-card">
            <div class="nx-card-body">
                <div class="text-danger">
                    <i class="ri-error-warning-line"></i>
                    <strong>{{ $totals['missing_purchase'] }} asset(s) have no purchase information</strong>, so they contribute nothing to the cost and book value figures above.
                    Record it under <a href="{{ route('admin.asset-purchases.index') }}">Purchase Information</a>.
                </div>
            </div>
        </div>
    @endif

    <div class="twin-row">

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Assets by Category</div>
                    <div class="nx-card-sub">How the register is distributed</div>
                </div>
            </div>
            <div class="nx-card-body">
                @if($byCategory->isEmpty())
                    <div class="text-center text-muted py-4">No assets to chart</div>
                @else
                    <canvas id="assetsByCategoryChart" height="200"></canvas>
                @endif
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Assets by State</div>
                    <div class="nx-card-sub">Where everything currently stands</div>
                </div>
            </div>
            <div class="nx-card-body">
                @if($byState->isEmpty())
                    <div class="text-center text-muted py-4">No assets to chart</div>
                @else
                    <canvas id="assetsByStateChart" height="200"></canvas>
                @endif
            </div>
        </div>

    </div>

    <div class="twin-row">

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Maintenance</div>
                    <div class="nx-card-sub">Workload and spend to date</div>
                </div>
            </div>
            <div class="nx-card-body">
                <table class="ractivity-tbl">
                    <tbody>
                        <tr>
                            <td>Active Schedules</td>
                            <td class="text-end">{{ $maintenance['active_schedules'] }}</td>
                        </tr>
                        <tr>
                            <td>Overdue</td>
                            <td class="text-end {{ $maintenance['overdue'] > 0 ? 'text-danger' : '' }}">{{ $maintenance['overdue'] }}</td>
                        </tr>
                        <tr>
                            <td>Due Within 30 Days</td>
                            <td class="text-end">{{ $maintenance['due_soon'] }}</td>
                        </tr>
                        <tr>
                            <td>Jobs Completed</td>
                            <td class="text-end">{{ $maintenance['jobs_done'] }}</td>
                        </tr>
                        <tr>
                            <td>Total Spend</td>
                            <td class="text-end">{{ number_format($maintenance['total_spend'], 2) }}</td>
                        </tr>
                        <tr>
                            <th>Total Downtime</th>
                            <th class="text-end">{{ rtrim(rtrim(number_format($maintenance['total_downtime'], 2), '0'), '.') }} hr</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Disposals</div>
                    <div class="nx-card-sub">Realised outcome on assets taken out of service</div>
                </div>
            </div>
            <div class="nx-card-body">
                <table class="ractivity-tbl">
                    <tbody>
                        <tr>
                            <td>Assets Disposed</td>
                            <td class="text-end">{{ $disposals['count'] }}</td>
                        </tr>
                        <tr>
                            <td>Total Proceeds</td>
                            <td class="text-end">{{ number_format($disposals['proceeds'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Book Value Disposed</td>
                            <td class="text-end">{{ number_format($disposals['book_value'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>At a Gain / At a Loss</td>
                            <td class="text-end">{{ $disposals['gains'] }} / {{ $disposals['losses'] }}</td>
                        </tr>
                        <tr>
                            <th>Net {{ $disposals['net'] >= 0 ? 'Gain' : 'Loss' }}</th>
                            <th class="text-end {{ $disposals['net'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format(abs($disposals['net']), 2) }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Overdue Maintenance</div>
                <div class="nx-card-sub">Schedules running late — soonest due first, up to 10 shown</div>
            </div>
        </div>
        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Schedule</th>
                            <th>Asset</th>
                            <th>Frequency</th>
                            <th>Due</th>
                            <th class="text-end">Days Late</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overdueMaintenance as $schedule)
                            <tr>
                                <td>{{ $schedule->title }}</td>
                                <td>
                                    {{ $schedule->asset->name ?? '-' }}
                                    <div class="text-muted small">{{ $schedule->asset->asset_code ?? '' }}</div>
                                </td>
                                <td>{{ $schedule->frequency_label }}</td>
                                <td class="text-danger">{{ $schedule->next_due_date->format('d M, Y') }}</td>
                                <td class="text-end text-danger">{{ abs($schedule->days_until_due) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nothing overdue — all schedules are on track</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">All Asset Screens</div>
                <div class="nx-card-sub">{{ $totals['in_store'] }} in store &middot; {{ $assignments['holders'] }} employee(s) holding assets</div>
            </div>
        </div>

        <div class="nx-card-body">
            @php
                $screens = [
                    ['route' => 'admin.assets.index', 'icon' => 'ri-hard-drive-3-line', 'title' => 'Asset Register', 'desc' => 'Every asset the business owns'],
                    ['route' => 'admin.asset-purchases.index', 'icon' => 'ri-money-dollar-box-line', 'title' => 'Purchase Information', 'desc' => 'How and for how much each asset was acquired'],
                    ['route' => 'admin.asset-assignments.index', 'icon' => 'ri-user-shared-2-line', 'title' => 'Asset Assignment', 'desc' => 'Who is holding which asset'],
                    ['route' => 'admin.employee-asset-tracking.index', 'icon' => 'ri-team-line', 'title' => 'Employee Asset Tracking', 'desc' => 'Holdings per employee'],
                    ['route' => 'admin.asset-location-movements.index', 'icon' => 'ri-route-line', 'title' => 'Location Tracking', 'desc' => 'Where each asset physically sits'],
                    ['route' => 'admin.asset-maintenance-schedules.index', 'icon' => 'ri-tools-line', 'title' => 'Maintenance Schedule', 'desc' => 'Planned work and what is due next'],
                    ['route' => 'admin.asset-maintenance-records.index', 'icon' => 'ri-file-history-line', 'title' => 'Maintenance History', 'desc' => 'Work actually carried out'],
                    ['route' => 'admin.asset-depreciation.index', 'icon' => 'ri-line-chart-line', 'title' => 'Depreciation', 'desc' => 'Book value and depreciation schedules'],
                    ['route' => 'admin.asset-disposals.index', 'icon' => 'ri-delete-bin-6-line', 'title' => 'Disposal Management', 'desc' => 'Assets taken out of service, with gain or loss'],
                ];
            @endphp

            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <tbody>
                        @foreach($screens as $screen)
                            <tr>
                                <td style="width:36px;"><i class="{{ $screen['icon'] }}"></i></td>
                                <td>
                                    <a href="{{ route($screen['route']) }}"><strong>{{ $screen['title'] }}</strong></a>
                                    <div class="text-muted small">{{ $screen['desc'] }}</div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route($screen['route']) }}" class="btn-nx-outline btn-sm">
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
    @if($byCategory->isNotEmpty() || $byState->isNotEmpty())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
        <script>
            @if($byCategory->isNotEmpty())
            new Chart(document.getElementById('assetsByCategoryChart'), {
                type: 'bar',
                data: {
                    labels: @json($byCategory->keys()),
                    datasets: [{
                        label: 'Assets',
                        data: @json($byCategory->values()),
                        backgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
            @endif

            @if($byState->isNotEmpty())
            new Chart(document.getElementById('assetsByStateChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($byState->keys()),
                    datasets: [{
                        data: @json($byState->values()),
                        backgroundColor: ['#22c55e', '#94a3b8', '#f59e0b', '#ef4444', '#8b5cf6']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
            @endif
        </script>
    @endif
@endpush
