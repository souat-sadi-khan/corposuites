@extends('admin.layout.app', ['title' => 'CRM Dashboard'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>CRM Overview</h2>
            <div class="sec-sub">Snapshot across leads, pipeline, activities, and quotations</div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Leads</div>
                <div class="stat-val">{{ number_format($totalLeads) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-user-add-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Active Leads</div>
                <div class="stat-val">{{ number_format($activeLeads) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-user-follow-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Contacts</div>
                <div class="stat-val">{{ number_format($totalContacts) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-contacts-book-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Companies</div>
                <div class="stat-val">{{ number_format($totalCompanies) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-building-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Open Opportunities</div>
                <div class="stat-val">{{ number_format($openOpportunityCount) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-hand-coin-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Open Pipeline Value</div>
                <div class="stat-val">{{ number_format($openPipelineValue, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-funds-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Won This Month</div>
                <div class="stat-val">{{ number_format($wonThisMonth) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-trophy-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Lost This Month</div>
                <div class="stat-val">{{ number_format($lostThisMonth) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-close-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Pending Activities</div>
                <div class="stat-val">{{ number_format($pendingActivities) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-phone-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Overdue Activities</div>
                <div class="stat-val">{{ number_format($overdueActivities) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-alarm-warning-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Leads by Source</div>
                    <div class="nx-card-sub">Active leads grouped by lead source</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="leadSourceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Leads by Pipeline Stage</div>
                    <div class="nx-card-sub">Active leads grouped by pipeline stage</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="leadStageChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Upcoming Follow Ups</div>
                    <div class="nx-card-sub">Next 5 pending reminders</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Remind At</th>
                                <th>Assigned To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingFollowUps as $followUp)
                                <tr>
                                    <td>{{ $followUp->title }}</td>
                                    <td>{{ $followUp->remind_at->format('d M, Y h:i A') }}</td>
                                    <td>{{ $followUp->assignedTo->name ?? 'Unassigned' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No upcoming follow ups</td>
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
                    <div class="nx-card-title">Quotations by Status</div>
                    <div class="nx-card-sub">Active quotations grouped by quotation status</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotationsByStatus as $row)
                                <tr>
                                    <td>{{ ucfirst($row->quotation_status) }}</td>
                                    <td>{{ number_format($row->total) }}</td>
                                    <td>{{ number_format($row->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No quotations yet</td>
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
        const sourceLabels = @json($leadsBySource->keys());
        const sourceData = @json($leadsBySource->values());

        new Chart(document.getElementById('leadSourceChart'), {
            type: 'bar',
            data: {
                labels: sourceLabels,
                datasets: [{
                    label: 'Leads',
                    data: sourceData,
                    backgroundColor: 'rgba(101,103,245,.7)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        const stageLabels = @json($leadsByStage->keys());
        const stageData = @json($leadsByStage->values());

        new Chart(document.getElementById('leadStageChart'), {
            type: 'doughnut',
            data: {
                labels: stageLabels,
                datasets: [{
                    data: stageData,
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
