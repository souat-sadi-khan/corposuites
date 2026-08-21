@extends('admin.layout.app', ['title' => 'Payroll Compliance Report'])

@section('content')

    <div class="sec-hdr d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2>Payroll Compliance Report</h2>
            <div class="sec-sub">Every active employee's current pay rate checked against today's Minimum Wage Rules</div>
        </div>

        <a href="{{ route('admin.minimum-wage-rules.index') }}" class="btn-nx-outline">
            <i class="ri-scales-3-line me-1"></i> Configure Rules
        </a>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="department_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ (string) $departmentId === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                @endforeach
            </select>

            <select name="pay_type" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Pay Types</option>
                <option value="monthly" {{ $payType === 'monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="daily" {{ $payType === 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="commission" {{ $payType === 'commission' ? 'selected' : '' }}>Commission-based</option>
            </select>

            <select name="compliance" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Compliance States</option>
                <option value="compliant" {{ $compliance === 'compliant' ? 'selected' : '' }}>Compliant</option>
                <option value="non_compliant" {{ $compliance === 'non_compliant' ? 'selected' : '' }}>Non-Compliant</option>
                <option value="no_rule" {{ $compliance === 'no_rule' ? 'selected' : '' }}>No Rule Configured</option>
                <option value="exempt" {{ $compliance === 'exempt' ? 'selected' : '' }}>Exempt (Commission)</option>
                <option value="no_structure" {{ $compliance === 'no_structure' ? 'selected' : '' }}>No Active Structure</option>
            </select>

            @if($departmentId || $payType || $compliance)
                <a href="{{ route('admin.payroll-compliance-report.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Employees Checked</div>
                <div class="stat-val">{{ number_format($summary['total']) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-group-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Compliant</div>
                <div class="stat-val">{{ number_format($summary['compliant']) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-shield-check-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Non-Compliant</div>
                <div class="stat-val">{{ number_format($summary['non_compliant']) }}</div>
            </div>
            <div class="stat-icon-wrap si-red">
                <i class="ri-error-warning-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">No Rule Configured</div>
                <div class="stat-val">{{ number_format($summary['no_rule']) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-question-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Exempt (Commission)</div>
                <div class="stat-val">{{ number_format($summary['exempt']) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-percent-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">No Active Structure</div>
                <div class="stat-val">{{ number_format($summary['no_structure']) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-file-forbid-line"></i>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Compliance Detail</div>
                <div class="nx-card-sub">Resolved live from each employee's current active Salary Structure and location — nothing here is stored, so it can never go stale</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Location</th>
                            <th>Pay Type</th>
                            <th class="text-end">Current Rate</th>
                            <th class="text-end">Minimum Wage</th>
                            <th class="text-end">Shortfall</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.employees.index', ['search' => $row['employee']->employee_code]) }}">{{ $row['employee']->full_name }}</a>
                                    <div class="text-muted small">{{ $row['employee']->employee_code }}</div>
                                </td>
                                <td>{{ $row['employee']->department?->name ?? '-' }}</td>
                                <td>
                                    @if($row['employee']->country)
                                        {{ $row['employee']->state ? $row['employee']->state . ', ' . $row['employee']->country : $row['employee']->country }}
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>{{ $row['structure']?->pay_type_label ?? '-' }}</td>
                                <td class="text-end">{{ $row['rate'] !== null ? format_currency($row['rate']) . ($row['pay_type'] === 'commission' ? '%' : '') : '-' }}</td>
                                <td class="text-end">{{ $row['rule'] ? format_currency($row['rule']->minimum_wage) : '-' }}</td>
                                <td class="text-end">
                                    @if($row['shortfall'])
                                        <span class="text-danger fw-bold">{{ format_currency($row['shortfall']) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @switch($row['status'])
                                        @case('compliant')
                                            <span class="badge bg-success-subtle text-success">Compliant</span>
                                            @break
                                        @case('non_compliant')
                                            <span class="badge bg-danger-subtle text-danger">Non-Compliant</span>
                                            @break
                                        @case('no_rule')
                                            <span class="badge bg-warning-subtle text-warning">No Rule Configured</span>
                                            @break
                                        @case('exempt')
                                            <span class="badge bg-secondary-subtle text-secondary">Exempt (Commission)</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary-subtle text-secondary">No Active Structure</span>
                                    @endswitch

                                    @if(in_array($row['status'], ['non_compliant', 'no_rule', 'no_structure']))
                                        <a href="{{ route('admin.salary-structures.index', ['employee_id' => $row['employee']->id]) }}" class="ms-1" title="Manage Salary Structure">
                                            <i class="ri-external-link-line"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No employees match the current filters</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
