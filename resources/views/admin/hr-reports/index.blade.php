@extends('admin.layout.app', ['title' => 'HR Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>HR Overview</h2>
            <div class="sec-sub">Aggregated snapshot across employees, attendance, leave, and payroll</div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Employees</div>
                <div class="stat-val">{{ number_format($totalEmployees) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-group-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Active Employees</div>
                <div class="stat-val">{{ number_format($activeEmployees) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-user-follow-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Pending Leave Requests</div>
                <div class="stat-val">{{ number_format($pendingLeaveRequests) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-mail-send-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Pending Expense Claims</div>
                <div class="stat-val">{{ number_format($pendingExpenseClaims) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-receipt-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Approved Leaves (This Month)</div>
                <div class="stat-val">{{ number_format($approvedLeaveRequestsThisMonth) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-calendar-check-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Expenses Approved (This Month)</div>
                <div class="stat-val">{{ number_format($approvedExpenseAmountThisMonth, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Outstanding Loan Balance</div>
                <div class="stat-val">{{ number_format($outstandingLoans, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-safe-2-line"></i>
            </div>
        </div>

        @php
            $paidThisMonth = $payrollThisMonth->firstWhere('payment_status', 'paid');
            $unpaidThisMonth = $payrollThisMonth->firstWhere('payment_status', 'unpaid');
        @endphp
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Payroll Paid (This Month)</div>
                <div class="stat-val">{{ number_format($paidThisMonth->amount ?? 0, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-hand-coin-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Employees by Department</div>
                    <div class="nx-card-sub">Active employees grouped by department</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Today's Attendance</div>
                    <div class="nx-card-sub">{{ now()->format('d M Y') }}</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="bottom-row">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Employees by Type</div>
                    <div class="nx-card-sub">Active employees grouped by employee type</div>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Employee Type</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employeesByType as $typeName => $count)
                            <tr>
                                <td>{{ $typeName }}</td>
                                <td>{{ number_format($count) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Payroll Summary (This Month)</div>
                    <div class="nx-card-sub">{{ now()->format('F Y') }}</div>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Payment Status</th>
                            <th>Count</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrollThisMonth as $row)
                            <tr>
                                <td>{{ ucfirst($row->payment_status) }}</td>
                                <td>{{ number_format($row->total) }}</td>
                                <td>{{ number_format($row->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No payroll generated this month</td></tr>
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
        const departmentLabels = @json($employeesByDepartment->pluck('department'));
        const departmentData = @json($employeesByDepartment->pluck('total'));

        new Chart(document.getElementById('departmentChart'), {
            type: 'bar',
            data: {
                labels: departmentLabels,
                datasets: [{
                    label: 'Employees',
                    data: departmentData,
                    backgroundColor: 'rgba(101,103,245,.7)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        const attendanceLabels = @json($attendanceToday->keys());
        const attendanceData = @json($attendanceToday->values());

        new Chart(document.getElementById('attendanceChart'), {
            type: 'doughnut',
            data: {
                labels: attendanceLabels,
                datasets: [{
                    data: attendanceData,
                    backgroundColor: ['#16a34a', '#dc2626', '#f59e0b', '#0ea5e9', '#a855f7']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
