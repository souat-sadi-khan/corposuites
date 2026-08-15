@extends('admin.layout.app', ['title' => 'Project Profitability Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Project Profitability Reports</h2>
            <div class="sec-sub">Revenue billed, cost incurred, budget vs. actual, and profit per project</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="project_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Projects</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" {{ (string) $projectId === (string) $project->id ? 'selected' : '' }}>{{ $project->name }} ({{ $project->project_code }})</option>
                @endforeach
            </select>

            @if($projectId)
                <a href="{{ route('admin.project-reports.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filter
                </a>
            @endif
        </form>
    </div>

    @if($mode === 'overview')

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Total Revenue Billed</div>
                    <div class="stat-val">{{ number_format($totals['revenue'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-green">
                    <i class="ri-bill-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Total Cost Incurred</div>
                    <div class="stat-val">{{ number_format($totals['cost'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-amber">
                    <i class="ri-money-dollar-circle-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Net {{ $totals['profit'] >= 0 ? 'Profit' : 'Loss' }}</div>
                    <div class="stat-val {{ $totals['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format(abs($totals['profit']), 2) }}</div>
                </div>
                <div class="stat-icon-wrap {{ $totals['profit'] < 0 ? 'si-red' : 'si-blue' }}">
                    <i class="ri-line-chart-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Overall Margin</div>
                    <div class="stat-val">{{ $totals['margin'] === null ? '-' : number_format($totals['margin'], 2) . '%' }}</div>
                </div>
                <div class="stat-icon-wrap si-purple">
                    <i class="ri-percent-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Outstanding Balance</div>
                    <div class="stat-val">{{ number_format($totals['outstanding'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-blue">
                    <i class="ri-wallet-3-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Over Budget</div>
                    <div class="stat-val {{ $totals['over_budget_projects'] > 0 ? 'text-danger' : '' }}">{{ $totals['over_budget_projects'] }} / {{ $totals['budgeted_projects'] }}</div>
                </div>
                <div class="stat-icon-wrap si-amber">
                    <i class="ri-error-warning-line"></i>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-body">
                <div class="text-muted small">
                    <i class="ri-information-line"></i>
                    <strong>{{ number_format($totals['total_hours'], 2) }} hour(s)</strong> logged across all projects ({{ number_format($totals['billable_hours'], 2) }} billable), shown for reference only —
                    Time Tracking carries no hourly rate anywhere in this project, so labour hours are <strong>not</strong> included in the cost/profit figures above.
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Profitability by Project</div>
                    <div class="nx-card-sub">Most profitable first</div>
                </div>
            </div>
            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Cost</th>
                                <th class="text-end">Profit</th>
                                <th class="text-end">Margin</th>
                                <th class="text-end">Budget</th>
                                <th class="text-end">Budget Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.project-reports.index', ['project_id' => $row['project']->id]) }}">{{ $row['project']->name }}</a>
                                        <div class="text-muted small">{{ $row['project']->project_code }}{{ $row['project']->client ? ' · ' . $row['project']->client->name : '' }}</div>
                                    </td>
                                    <td class="text-end">{{ number_format($row['revenue'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['cost'], 2) }}</td>
                                    <td class="text-end {{ $row['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['profit'], 2) }}</td>
                                    <td class="text-end">{{ $row['margin'] === null ? '-' : number_format($row['margin'], 2) . '%' }}</td>
                                    <td class="text-end">
                                        @if($row['budget_total'] === null)
                                            <span class="text-muted">No budget</span>
                                        @else
                                            {{ number_format($row['budget_total'], 2) }} <span class="text-muted small">({{ $row['budget_version'] }})</span>
                                        @endif
                                    </td>
                                    <td class="text-end {{ $row['budget_variance'] !== null && $row['budget_variance'] < 0 ? 'text-danger' : '' }}">
                                        {{ $row['budget_variance'] === null ? '-' : number_format($row['budget_variance'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No active projects to report on</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @else

        {{-- Detail mode: one project's full profitability picture --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Revenue Billed</div>
                    <div class="stat-val">{{ number_format($row['revenue'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-green">
                    <i class="ri-bill-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Cost Incurred</div>
                    <div class="stat-val">{{ number_format($row['cost'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-amber">
                    <i class="ri-money-dollar-circle-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">{{ $row['profit'] >= 0 ? 'Profit' : 'Loss' }}</div>
                    <div class="stat-val {{ $row['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format(abs($row['profit']), 2) }}</div>
                </div>
                <div class="stat-icon-wrap {{ $row['profit'] < 0 ? 'si-red' : 'si-blue' }}">
                    <i class="ri-line-chart-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Margin</div>
                    <div class="stat-val">{{ $row['margin'] === null ? '-' : number_format($row['margin'], 2) . '%' }}</div>
                </div>
                <div class="stat-icon-wrap si-purple">
                    <i class="ri-percent-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Outstanding Balance</div>
                    <div class="stat-val">{{ number_format($row['outstanding'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-blue">
                    <i class="ri-wallet-3-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-lbl">Recoverable (Billable) Cost</div>
                    <div class="stat-val">{{ number_format($row['billable_cost'], 2) }}</div>
                </div>
                <div class="stat-icon-wrap si-amber">
                    <i class="ri-refund-2-line"></i>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">{{ $project->name }}</div>
                    <div class="nx-card-sub">{{ $project->project_code }}{{ $project->client ? ' · ' . $project->client->name : '' }} &middot; {{ number_format($row['total_hours'], 2) }} hour(s) logged ({{ number_format($row['billable_hours'], 2) }} billable, not costed — no hourly rate exists in Time Tracking)</div>
                </div>
                <a href="{{ route('admin.projects.index') }}?search={{ urlencode($project->project_code) }}" class="btn-nx-outline btn-sm">
                    <i class="ri-external-link-line"></i> Open Project
                </a>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Budget vs. Actual</div>
                    <div class="nx-card-sub">
                        @if($budget)
                            Current budget {{ $budget->version_label }}, {{ number_format($budget->total_amount, 2) }} total
                        @else
                            This project has no active budget on file
                        @endif
                    </div>
                </div>
            </div>
            <div class="nx-card-body">
                @if(! $budget && $categoryBreakdown->isEmpty())
                    <div class="text-center text-muted py-3">No budget and no approved expenses recorded for this project yet.</div>
                @else
                    @if(! $budget)
                        <div class="text-danger mb-2">
                            <i class="ri-error-warning-line"></i>
                            No <a href="{{ route('admin.project-budgets.index', ['project_id' => $project->id]) }}">budget</a> has been recorded — actual spend below has nothing to be measured against.
                        </div>
                    @endif
                    <div style="overflow-x:auto;">
                        <table class="ractivity-tbl">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Budgeted</th>
                                    <th class="text-end">Actual</th>
                                    <th class="text-end">Variance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categoryBreakdown as $line)
                                    <tr>
                                        <td>{{ $line['label'] }}</td>
                                        <td class="text-end">{{ number_format($line['budgeted'], 2) }}</td>
                                        <td class="text-end">{{ number_format($line['actual'], 2) }}</td>
                                        <td class="text-end {{ $line['variance'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($line['variance'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Nothing to break down by category yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($categoryBreakdown->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">{{ number_format($categoryBreakdown->sum('budgeted'), 2) }}</th>
                                        <th class="text-end">{{ number_format($categoryBreakdown->sum('actual'), 2) }}</th>
                                        <th class="text-end {{ $categoryBreakdown->sum('variance') < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($categoryBreakdown->sum('variance'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    @if($budget && $row['budget_used_percent'] !== null)
                        <div class="mt-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Budget Used</span>
                                <span>{{ number_format($row['budget_used_percent'], 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $row['budget_used_percent'] > 100 ? 'bg-danger' : 'bg-success' }}" style="width: {{ min(100, $row['budget_used_percent']) }}%"></div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>

    @endif

@endsection
