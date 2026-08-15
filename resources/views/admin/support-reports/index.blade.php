@extends('admin.layout.app', ['title' => 'Support Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Support Reports</h2>
            <div class="sec-sub">Headline figures from across the Support module</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="ticket_category_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" class="form-control form-control-sm w-auto" value="{{ $dateFrom }}" placeholder="From" onchange="this.form.submit()">
            <input type="date" name="date_to" class="form-control form-control-sm w-auto" value="{{ $dateTo }}" placeholder="To" onchange="this.form.submit()">

            @if($categoryId || $dateFrom || $dateTo)
                <a href="{{ route('admin.support-reports.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Tickets</div>
                <div class="stat-val">{{ $totals['total_tickets'] }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-customer-service-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Open Tickets</div>
                <div class="stat-val">{{ $totals['open_tickets'] }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-time-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Overdue Tickets</div>
                <div class="stat-val {{ $totals['overdue_tickets'] > 0 ? 'text-danger' : '' }}">{{ $totals['overdue_tickets'] }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-alarm-warning-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Response Breaches</div>
                <div class="stat-val {{ $totals['response_breaches'] > 0 ? 'text-danger' : '' }}">{{ $totals['response_breaches'] }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-flashlight-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Resolution Breaches</div>
                <div class="stat-val {{ $totals['resolution_breaches'] > 0 ? 'text-danger' : '' }}">{{ $totals['resolution_breaches'] }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-error-warning-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Avg. Resolution Time</div>
                <div class="stat-val">
                    @if($totals['avg_resolution_hours'] !== null)
                        {{ rtrim(rtrim(number_format($totals['avg_resolution_hours'], 1), '0'), '.') }} hr
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-timer-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Without SLA Target</div>
                <div class="stat-val">{{ $totals['without_sla'] }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-shield-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Unassigned Tickets</div>
                <div class="stat-val {{ $assignments['unassigned'] > 0 ? 'text-danger' : '' }}">{{ $assignments['unassigned'] }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-user-unfollow-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row">

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Tickets by Category</div>
                    <div class="nx-card-sub">How the workload is distributed</div>
                </div>
            </div>
            <div class="nx-card-body">
                @if($byCategory->isEmpty())
                    <div class="text-center text-muted py-4">No tickets to chart</div>
                @else
                    <canvas id="ticketsByCategoryChart" height="200"></canvas>
                @endif
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Tickets by State</div>
                    <div class="nx-card-sub">Where everything currently stands</div>
                </div>
            </div>
            <div class="nx-card-body">
                @if($byStatus->isEmpty())
                    <div class="text-center text-muted py-4">No tickets to chart</div>
                @else
                    <canvas id="ticketsByStatusChart" height="200"></canvas>
                @endif
            </div>
        </div>

    </div>

    <div class="twin-row">

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Handling</div>
                    <div class="nx-card-sub">Assignments and escalations for the tickets in scope</div>
                </div>
            </div>
            <div class="nx-card-body">
                <table class="ractivity-tbl">
                    <tbody>
                        <tr>
                            <td>Active Assignments</td>
                            <td class="text-end">{{ $assignments['active'] }}</td>
                        </tr>
                        <tr>
                            <td>Agents Currently Handling</td>
                            <td class="text-end">{{ $assignments['agents'] }}</td>
                        </tr>
                        <tr>
                            <td>Escalations Recorded</td>
                            <td class="text-end">{{ $escalations['total'] }}</td>
                        </tr>
                        <tr>
                            <td>&mdash; With a Priority Change</td>
                            <td class="text-end">{{ $escalations['priority_changed'] }}</td>
                        </tr>
                        <tr>
                            <td>&mdash; Reassigned to an Agent</td>
                            <td class="text-end">{{ $escalations['reassigned'] }}</td>
                        </tr>
                        <tr>
                            <th>Active Escalation Rules</th>
                            <th class="text-end">{{ $escalations['active_rules'] }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Knowledge Base</div>
                    <div class="nx-card-sub">Content library snapshot &mdash; not scoped by the filters above</div>
                </div>
            </div>
            <div class="nx-card-body">
                <table class="ractivity-tbl">
                    <tbody>
                        <tr>
                            <td>Total Articles</td>
                            <td class="text-end">{{ $knowledgeBase['total'] }}</td>
                        </tr>
                        <tr>
                            <td>Published</td>
                            <td class="text-end">{{ $knowledgeBase['published'] }}</td>
                        </tr>
                        <tr>
                            <td>Draft</td>
                            <td class="text-end">{{ $knowledgeBase['draft'] }}</td>
                        </tr>
                        <tr>
                            <td>Archived</td>
                            <td class="text-end">{{ $knowledgeBase['archived'] }}</td>
                        </tr>
                        <tr>
                            <th>Public</th>
                            <th class="text-end">{{ $knowledgeBase['public'] }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Recent Escalations</div>
                <div class="nx-card-sub">Most recent first, up to 10 shown</div>
            </div>
        </div>
        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Rule</th>
                            <th>Priority Change</th>
                            <th>Reassigned To</th>
                            <th>Escalated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentEscalations as $escalation)
                            <tr>
                                <td>
                                    {{ $escalation->ticket->subject ?? 'Ticket removed' }}
                                    <div class="text-muted small">{{ $escalation->ticket->ticket_number ?? '' }}</div>
                                </td>
                                <td>{{ $escalation->escalationRule->name ?? '-' }}</td>
                                <td>
                                    @if($escalation->previous_priority && $escalation->new_priority && $escalation->previous_priority !== $escalation->new_priority)
                                        {{ ucfirst($escalation->previous_priority) }} &rarr; {{ ucfirst($escalation->new_priority) }}
                                    @else
                                        Unchanged
                                    @endif
                                </td>
                                <td>{{ $escalation->escalatedToAdmin->name ?? '-' }}</td>
                                <td>{{ $escalation->escalated_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No escalations recorded</td>
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
                <div class="nx-card-title">All Support Screens</div>
                <div class="nx-card-sub">{{ $totals['total_tickets'] }} ticket(s) in scope &middot; {{ $knowledgeBase['total'] }} knowledge base article(s)</div>
            </div>
        </div>

        <div class="nx-card-body">
            @php
                $screens = [
                    ['route' => 'admin.tickets.index', 'icon' => 'ri-customer-service-line', 'title' => 'Tickets', 'desc' => 'Every support ticket raised'],
                    ['route' => 'admin.ticket-assignments.index', 'icon' => 'ri-user-follow-line', 'title' => 'Ticket Assignment', 'desc' => 'Who is handling each ticket'],
                    ['route' => 'admin.ticket-escalations.index', 'icon' => 'ri-arrow-up-double-line', 'title' => 'Escalation History', 'desc' => 'Every time a ticket was actually escalated'],
                    ['route' => 'admin.knowledge-base-articles.index', 'icon' => 'ri-book-open-line', 'title' => 'Knowledge Base', 'desc' => 'Support documentation and articles'],
                    ['route' => 'admin.ticket-categories.index', 'icon' => 'ri-price-tag-3-line', 'title' => 'Ticket Categories', 'desc' => 'Classification for incoming tickets'],
                    ['route' => 'admin.ticket-statuses.index', 'icon' => 'ri-flag-line', 'title' => 'Ticket Statuses', 'desc' => 'Configurable sub-statuses'],
                    ['route' => 'admin.ticket-priorities.index', 'icon' => 'ri-alarm-warning-line', 'title' => 'Ticket Priorities', 'desc' => 'Configurable sub-priorities'],
                    ['route' => 'admin.sla-policies.index', 'icon' => 'ri-time-line', 'title' => 'SLA Policies', 'desc' => 'Response and resolution targets by priority'],
                    ['route' => 'admin.escalation-rules.index', 'icon' => 'ri-arrow-up-double-line', 'title' => 'Escalation Rules', 'desc' => 'What happens when an SLA is breached'],
                    ['route' => 'admin.knowledge-base-categories.index', 'icon' => 'ri-folder-3-line', 'title' => 'KB Categories', 'desc' => 'Knowledge base classification'],
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
    @if($byCategory->isNotEmpty() || $byStatus->isNotEmpty())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
        <script>
            @if($byCategory->isNotEmpty())
            new Chart(document.getElementById('ticketsByCategoryChart'), {
                type: 'bar',
                data: {
                    labels: @json($byCategory->keys()),
                    datasets: [{
                        label: 'Tickets',
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

            @if($byStatus->isNotEmpty())
            new Chart(document.getElementById('ticketsByStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($byStatus->keys()),
                    datasets: [{
                        data: @json($byStatus->values()),
                        backgroundColor: ['#22c55e', '#3b82f6', '#f59e0b', '#8b5cf6', '#94a3b8']
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
