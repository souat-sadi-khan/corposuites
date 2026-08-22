@extends('admin.layout.app', ['title' => 'Expense Claims Report'])

@section('content')

    <div class="sec-hdr d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2>Expense Claims Report</h2>
            <div class="sec-sub">Spend, approvals and reimbursement status across every filed expense claim</div>
        </div>

        <a href="{{ route('admin.expense-categories.index') }}" class="btn-nx-outline">
            <i class="ri-price-tag-3-line me-1"></i> Manage Categories
        </a>
    </div>

    <div class="tl-toolbar fm-body mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="expense_category_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) request('expense_category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>

            <select name="department_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ (string) request('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                @endforeach
            </select>

            <select name="employee_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ (string) request('employee_id') === (string) $employee->id ? 'selected' : '' }}>{{ $employee->full_name }}</option>
                @endforeach
            </select>

            <select name="approval_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Approvals</option>
                <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>

            <select name="payment_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Payments</option>
                <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Reimbursed</option>
            </select>

            <input type="date" name="date_from" class="form-control form-control-sm w-auto" value="{{ request('date_from') }}" onchange="this.form.submit()" title="From date">
            <input type="date" name="date_to" class="form-control form-control-sm w-auto" value="{{ request('date_to') }}" onchange="this.form.submit()" title="To date">

            @if(request()->anyFilled(['expense_category_id', 'department_id', 'employee_id', 'approval_status', 'payment_status', 'date_from', 'date_to']))
                <a href="{{ route('admin.expense-claims-report.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Claims</div>
                <div class="stat-val">{{ number_format($summary['total_claims']) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-receipt-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Amount</div>
                <div class="stat-val">{{ format_currency($summary['total_amount']) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Pending Approval</div>
                <div class="stat-val">{{ number_format($summary['pending']) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-time-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Approved</div>
                <div class="stat-val">{{ number_format($summary['approved']) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-shield-check-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Rejected</div>
                <div class="stat-val">{{ number_format($summary['rejected']) }}</div>
            </div>
            <div class="stat-icon-wrap si-red">
                <i class="ri-close-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Awaiting Reimbursement</div>
                <div class="stat-val">{{ format_currency($summary['unreimbursed_amount']) }}</div>
            </div>
            <div class="stat-icon-wrap si-red">
                <i class="ri-bank-card-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Spend by Category</div>
                    <div class="nx-card-sub">Filed against the dynamic Expense Categories master</div>
                </div>
            </div>
            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-end">Claims</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Approved Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byCategory as $name => $row)
                                <tr>
                                    <td>{{ $name }}</td>
                                    <td class="text-end">{{ number_format($row['count']) }}</td>
                                    <td class="text-end">{{ format_currency($row['total']) }}</td>
                                    <td class="text-end">{{ format_currency($row['approved_total']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No claims match the current filters</td>
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
                    <div class="nx-card-title">Spend by Department</div>
                    <div class="nx-card-sub">Resolved from each claim's employee</div>
                </div>
            </div>
            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th class="text-end">Claims</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byDepartment as $name => $row)
                                <tr>
                                    <td>{{ $name }}</td>
                                    <td class="text-end">{{ number_format($row['count']) }}</td>
                                    <td class="text-end">{{ format_currency($row['total']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No claims match the current filters</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Claim Detail</div>
                <div class="nx-card-sub">{{ number_format($claims->count()) }} claim(s) matching the current filters</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Approval</th>
                            <th>Reimbursement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($claims as $claim)
                            <tr>
                                <td>
                                    {{ $claim->employee->full_name ?? '-' }}
                                    <div class="text-muted small">{{ $claim->employee->department->name ?? '' }}</div>
                                </td>
                                <td>{{ $claim->expenseCategory->name ?? ($claim->category_legacy ?: 'Uncategorised') }}</td>
                                <td>{{ $claim->expense_date?->format('d-m-Y') }}</td>
                                <td class="text-end">{{ format_currency($claim->amount) }}</td>
                                <td>
                                    @switch($claim->approval_status)
                                        @case('approved')
                                            <span class="badge bg-success-subtle text-success">Approved</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge bg-danger-subtle text-danger">Rejected</span>
                                            @break
                                        @default
                                            <span class="badge bg-warning-subtle text-warning">Pending</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($claim->payment_status === 'paid')
                                        <span class="badge bg-success-subtle text-success">Reimbursed</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Unpaid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No claims match the current filters</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
