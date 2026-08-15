@extends('admin.layout.app', ['title' => 'Sales Forecasting'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Sales Forecast</h2>
            <div class="sec-sub">Projected revenue from open opportunities in the pipeline</div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Pipeline Value</div>
                <div class="stat-val">{{ number_format($totalPipelineValue, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-funds-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Weighted Forecast (by Probability)</div>
                <div class="stat-val">{{ number_format($weightedForecastValue, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-line-chart-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Won This Month</div>
                <div class="stat-val">{{ number_format($wonThisMonth, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-trophy-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Pipeline by Stage</div>
                    <div class="nx-card-sub">Open and closed opportunities grouped by stage</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="stageChart"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Forecast by Expected Close Month</div>
                    <div class="nx-card-sub">Open opportunities grouped by expected close month</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Opportunities</th>
                                <th>Total Value</th>
                                <th>Weighted Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byMonth as $month => $row)
                                <tr>
                                    <td>{{ $month }}</td>
                                    <td>{{ number_format($row->total) }}</td>
                                    <td>{{ number_format($row->amount, 2) }}</td>
                                    <td>{{ number_format($row->weighted, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No open opportunities with an expected close date</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script>
        const stageLabels = @json($byStage->pluck('stage')->map(fn($s) => ucfirst($s)));
        const stageData = @json($byStage->pluck('amount'));

        new Chart(document.getElementById('stageChart'), {
            type: 'bar',
            data: {
                labels: stageLabels,
                datasets: [{
                    label: 'Amount',
                    data: stageData,
                    backgroundColor: 'rgba(101,103,245,.7)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
