@extends('admin.layout.app', ['title' => 'Employee Asset Tracking'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Employee Asset Tracking</h2>
            <div class="sec-sub">Who is holding which assets, built from the Asset Assignment history</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="employee_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Employees (Summary)</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ (string) $employeeId === (string) $employee->id ? 'selected' : '' }}>
                        {{ trim($employee->first_name . ' ' . $employee->last_name) }} ({{ $employee->employee_code }})
                    </option>
                @endforeach
            </select>

            <select name="only_current" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Include Returned</option>
                <option value="1" {{ $onlyCurrent ? 'selected' : '' }}>Currently Held Only</option>
            </select>

            @if($employeeId || $onlyCurrent)
                <a href="{{ route('admin.employee-asset-tracking.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Assets Out</div>
                <div class="stat-val">{{ $totals['assets_out'] }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-hard-drive-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Employees Holding</div>
                <div class="stat-val">{{ $totals['employees_holding'] }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-team-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Overdue Returns</div>
                <div class="stat-val {{ $totals['overdue'] > 0 ? 'text-danger' : '' }}">{{ $totals['overdue'] }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-alarm-warning-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Lifetime Assignments</div>
                <div class="stat-val">{{ $totals['lifetime_assignments'] }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-history-line"></i>
            </div>
        </div>
    </div>

    @if($selectedEmployee && $holdings)

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">{{ trim($selectedEmployee->first_name . ' ' . $selectedEmployee->last_name) }}</div>
                    <div class="nx-card-sub">
                        {{ $selectedEmployee->employee_code }} &middot;
                        {{ $holdings['currently_held'] }} currently held &middot;
                        {{ $holdings['returned'] }} returned
                        @if($holdings['overdue'] > 0)
                            &middot; <span class="text-danger">{{ $holdings['overdue'] }} overdue</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Asset</th>
                                <th>Category</th>
                                <th>Assigned</th>
                                <th>Return</th>
                                <th>Condition</th>
                                <th>State</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holdings['assignments'] as $assignment)
                                <tr>
                                    <td>
                                        @if($assignment->asset)
                                            <strong>{{ $assignment->asset->name }}</strong>
                                            <div class="text-muted small">{{ $assignment->asset->asset_code }}</div>
                                        @else
                                            <span class="text-danger">Asset removed</span>
                                        @endif
                                    </td>
                                    <td>{{ $assignment->asset?->assetCategory?->name ?? '-' }}</td>
                                    <td>{{ $assignment->assigned_date->format('d M, Y') }}</td>
                                    <td class="{{ $assignment->is_overdue ? 'text-danger' : '' }}">
                                        @if($assignment->returned_date)
                                            {{ $assignment->returned_date->format('d M, Y') }}
                                        @elseif($assignment->expected_return_date)
                                            Due {{ $assignment->expected_return_date->format('d M, Y') }}
                                            @if($assignment->is_overdue)
                                                <div class="small">Overdue</div>
                                            @endif
                                        @else
                                            <span class="text-muted">Open-ended</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ ucfirst($assignment->condition_on_assign) }}
                                        @if($assignment->condition_on_return)
                                            <span class="text-muted">&rarr; {{ ucfirst($assignment->condition_on_return) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $map = ['assigned' => 'bg-success', 'returned' => 'bg-secondary', 'cancelled' => 'bg-danger'];
                                        @endphp
                                        <span class="badge {{ $map[$assignment->assignment_status] ?? 'bg-secondary' }}">{{ ucfirst($assignment->assignment_status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No assignment records for this employee</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @else

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Employees Holding Assets</div>
                    <div class="nx-card-sub">Only employees with assignment history are listed — select one above for their full record</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Code</th>
                                <th class="text-end">Currently Held</th>
                                <th class="text-end">Overdue</th>
                                <th class="text-end">Returned</th>
                                <th class="text-end">Lifetime</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.employee-asset-tracking.index', array_filter(['employee_id' => $row['employee_id'], 'only_current' => $onlyCurrent])) }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td>{{ $row['employee_code'] }}</td>
                                    <td class="text-end">{{ $row['currently_held'] }}</td>
                                    <td class="text-end {{ $row['overdue'] > 0 ? 'text-danger' : '' }}">{{ $row['overdue'] > 0 ? $row['overdue'] : '-' }}</td>
                                    <td class="text-end">{{ $row['returned'] }}</td>
                                    <td class="text-end">{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No assets have been assigned to any employee yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @endif

@endsection
