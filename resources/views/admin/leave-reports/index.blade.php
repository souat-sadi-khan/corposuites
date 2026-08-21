@extends('admin.layout.app', ['title' => 'Leave Reports'])

@section('content')

    <div class="sec-hdr d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2>Leave Reports</h2>
            <div class="sec-sub">Balance utilization, trends, and status analytics for {{ $year }}</div>
        </div>

        <form method="GET" action="{{ route('admin.leave-reports.index') }}" class="d-flex align-items-center gap-2">
            <label class="mb-0"><small>Year</small></label>
            <select name="year" class="form-select" style="width:auto;" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ (int) $y === (int) $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Active Employees</div>
                <div class="stat-val">{{ number_format($totalEmployees) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-group-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Pending Requests</div>
                <div class="stat-val">{{ number_format($pendingRequests) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-mail-send-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Approved Requests ({{ $year }})</div>
                <div class="stat-val">{{ number_format($approvedThisYear) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-calendar-check-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Leave Days Taken ({{ $year }})</div>
                <div class="stat-val">{{ number_format($daysTakenThisYear, 1) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-calendar-todo-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Utilization by Leave Type</div>
                    <div class="nx-card-sub">Allocated vs used days ({{ $year }})</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="utilizationChart"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Monthly Trend</div>
                    <div class="nx-card-sub">Approved leave days by month ({{ $year }})</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Request Status Breakdown</div>
                    <div class="nx-card-sub">All requests starting in {{ $year }}</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Balance Summary by Type</div>
                    <div class="nx-card-sub">Aggregated across all employees ({{ $year }})</div>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Allocated</th>
                            <th>Used</th>
                            <th>Remaining</th>
                            <th>Utilization</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($utilization as $row)
                            <tr>
                                <td>{{ $row->name }}</td>
                                <td>{{ number_format($row->allocated, 1) }}</td>
                                <td>{{ number_format($row->used, 1) }}</td>
                                <td>{{ number_format($row->remaining, 1) }}</td>
                                <td>{{ number_format($row->utilization_pct, 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No balance data for {{ $year }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bottom-row">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Top Leave Takers</div>
                    <div class="nx-card-sub">Most approved leave days in {{ $year }}</div>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Requests</th>
                            <th>Days Taken</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topTakers as $row)
                            <tr>
                                <td>{{ $row->employee->full_name ?? ('#' . $row->employee_id) }}</td>
                                <td>{{ number_format($row->requests) }}</td>
                                <td>{{ number_format($row->days, 1) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No approved leave in {{ $year }}</td></tr>
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
        const utilLabels = @json($utilization->pluck('name'));
        const utilAllocated = @json($utilization->pluck('allocated'));
        const utilUsed = @json($utilization->pluck('used'));

        new Chart(document.getElementById('utilizationChart'), {
            type: 'bar',
            data: {
                labels: utilLabels,
                datasets: [
                    { label: 'Allocated', data: utilAllocated, backgroundColor: 'rgba(101,103,245,.7)', borderRadius: 4 },
                    { label: 'Used', data: utilUsed, backgroundColor: 'rgba(22,163,74,.7)', borderRadius: 4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });

        const monthLabels = @json($monthlyTrend->pluck('label'));
        const monthData = @json($monthlyTrend->pluck('days'));

        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Days',
                    data: monthData,
                    borderColor: '#6567f5',
                    backgroundColor: 'rgba(101,103,245,.15)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });

        const statusLabels = @json($statusBreakdown->keys());
        const statusData = @json($statusBreakdown->values());

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#16a34a', '#f59e0b', '#dc2626', '#0ea5e9', '#a855f7']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    </script>
@endpush
