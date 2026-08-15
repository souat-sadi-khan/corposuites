@extends('admin.layout.app', ['title' => 'CRM Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>CRM Reports</h2>
            <div class="sec-sub">Conversion, win-rate, and engagement metrics across the CRM</div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Lead Conversion Rate</div>
                <div class="stat-val">{{ $leadConversionRate }}%</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-percent-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Opportunity Win Rate</div>
                <div class="stat-val">{{ $opportunityWinRate }}%</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-trophy-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Activity Completion Rate</div>
                <div class="stat-val">{{ $activityCompletionRate }}%</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-phone-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Quotation Acceptance Rate</div>
                <div class="stat-val">{{ $quotationConversion }}%</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-file-list-3-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Opportunities by Stage</div>
                    <div class="nx-card-sub">Active opportunities grouped by pipeline stage</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Stage</th>
                                <th>Count</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($opportunitiesByStage as $row)
                                <tr>
                                    <td>{{ ucfirst($row->stage) }}</td>
                                    <td>{{ number_format($row->total) }}</td>
                                    <td>{{ number_format($row->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No opportunities yet</td>
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
                    <div class="nx-card-title">Email Volume by Direction</div>
                    <div class="nx-card-sub">Inbound vs outbound emails logged</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="emailDirectionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">New Leads by Source (This Month)</div>
                <div class="nx-card-sub">{{ now()->format('F Y') }}</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>New Leads</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leadsBySourceMonthly as $source => $total)
                            <tr>
                                <td>{{ $source }}</td>
                                <td>{{ number_format($total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No new leads this month</td>
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
        const emailLabels = @json($emailVolumeByDirection->keys()->map(fn($d) => ucfirst($d)));
        const emailData = @json($emailVolumeByDirection->values());

        new Chart(document.getElementById('emailDirectionChart'), {
            type: 'doughnut',
            data: {
                labels: emailLabels,
                datasets: [{
                    data: emailData,
                    backgroundColor: ['#0ea5e9', '#a855f7']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
