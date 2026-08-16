<form class="ajax-form" method="POST" action="#">
    <div class="offcanvas-header">
        <div>
            <h5 class="offcanvas-title">{{ $employee->full_name }}</h5>
            <p>{{ $employee->employee_code }} &middot; {{ $employee->email }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body px-3">
        <ul class="nav nav-tabs flex-nowrap overflow-auto" id="generalTabs" role="tablist" style="white-space:nowrap;">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button">Profile</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">Documents</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contacts" type="button">Emergency Contacts</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank" type="button">Bank Accounts</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-education" type="button">Education</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-experience" type="button">Experience</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-career" type="button">Career Events</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attendance" type="button">Attendance</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-leave" type="button">Leave</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payroll" type="button">Salary & Payroll</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-finance" type="button">Claims & Loans</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reviews" type="button">Performance</button></li>
        </ul>

        <div class="tab-content pt-3">
            <!-- Profile -->
            <div class="tab-pane fade show active" id="tab-profile">
                <div class="fm-grid">
                    <div class="fm-field"><label>Full Name</label><div>{{ $employee->full_name }}</div></div>
                    <div class="fm-field"><label>Phone</label><div>{{ $employee->phone ?? '-' }}</div></div>
                    <div class="fm-field"><label>Gender</label><div>{{ ucfirst($employee->gender ?? '-') }}</div></div>
                    <div class="fm-field"><label>Date of Birth</label><div>{{ $employee->date_of_birth?->format('d-m-Y') ?? '-' }}</div></div>
                    <div class="fm-field"><label>Date of Joining</label><div>{{ $employee->date_of_joining?->format('d-m-Y') ?? '-' }}</div></div>
                    <div class="fm-field"><label>Department</label><div>{{ $employee->department->name ?? '-' }}</div></div>
                    <div class="fm-field"><label>Designation</label><div>{{ $employee->designation->name ?? '-' }}</div></div>
                    <div class="fm-field"><label>Employee Type</label><div>{{ $employee->employeeType->name ?? '-' }}</div></div>
                    <div class="fm-field"><label>Employment Status</label><div>{{ $employee->employmentStatus->name ?? '-' }}</div></div>
                    <div class="fm-field"><label>Shift</label><div>{{ $employee->shift->name ?? '-' }}</div></div>
                    <div class="fm-field"><label>Login Account</label><div>{{ $employee->admin?->email ?? 'No login account' }}</div></div>
                    <div class="fm-field fm-full"><label>Address</label><div>{{ $employee->address ?? '-' }}</div></div>
                </div>
            </div>

            <!-- Documents -->
            <div class="tab-pane fade" id="tab-documents">
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Title</th><th>Expiry</th><th>File</th></tr></thead>
                    <tbody>
                        @forelse($employee->documents as $doc)
                            <tr>
                                <td>{{ $doc->title }}</td>
                                <td>{{ $doc->expiry_date?->format('d-m-Y') ?? '-' }}</td>
                                <td><a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No documents</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Emergency Contacts -->
            <div class="tab-pane fade" id="tab-contacts">
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Name</th><th>Relationship</th><th>Phone</th></tr></thead>
                    <tbody>
                        @forelse($employee->emergencyContacts as $contact)
                            <tr>
                                <td>{{ $contact->name }} @if($contact->is_primary)<span class="badge bg-success-subtle text-success">Primary</span>@endif</td>
                                <td>{{ $contact->relationship }}</td>
                                <td>{{ $contact->phone }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No emergency contacts</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Bank Accounts -->
            <div class="tab-pane fade" id="tab-bank">
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Bank</th><th>Account Number</th><th>Branch</th></tr></thead>
                    <tbody>
                        @forelse($employee->bankAccounts as $bank)
                            <tr>
                                <td>{{ $bank->bank_name }} @if($bank->is_primary)<span class="badge bg-success-subtle text-success">Primary</span>@endif</td>
                                <td>{{ $bank->account_number }}</td>
                                <td>{{ $bank->branch_name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No bank accounts</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Education -->
            <div class="tab-pane fade" id="tab-education">
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Degree</th><th>Institution</th><th>Duration</th></tr></thead>
                    <tbody>
                        @forelse($employee->educations as $edu)
                            <tr>
                                <td>{{ $edu->degree }}</td>
                                <td>{{ $edu->institution }}</td>
                                <td>{{ $edu->start_year ?? '-' }} - {{ $edu->end_year ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No education records</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Experience -->
            <div class="tab-pane fade" id="tab-experience">
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Company</th><th>Designation</th><th>Duration</th></tr></thead>
                    <tbody>
                        @forelse($employee->experiences as $exp)
                            <tr>
                                <td>{{ $exp->company_name }}</td>
                                <td>{{ $exp->designation }}</td>
                                <td>{{ $exp->start_date?->format('M Y') }} - {{ $exp->is_current ? 'Present' : ($exp->end_date?->format('M Y') ?? '-') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No experience records</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Career Events: Transfers, Promotions, Resignations, Terminations -->
            <div class="tab-pane fade" id="tab-career">
                <h6>Transfers</h6>
                <table class="ractivity-tbl w-100 mb-3">
                    <thead><tr><th>Department</th><th>Designation</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($employee->transfers as $t)
                            <tr><td>{{ $t->from_department }} &rarr; {{ $t->to_department }}</td><td>{{ $t->from_designation }} &rarr; {{ $t->to_designation }}</td><td>{{ $t->transfer_date?->format('d-m-Y') }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No transfers</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <h6>Promotions</h6>
                <table class="ractivity-tbl w-100 mb-3">
                    <thead><tr><th>Designation</th><th>Salary</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($employee->promotions as $p)
                            <tr><td>{{ $p->from_designation }} &rarr; {{ $p->to_designation }}</td><td>{{ $p->from_salary }} &rarr; {{ $p->to_salary }}</td><td>{{ $p->promotion_date?->format('d-m-Y') }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No promotions</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <h6>Resignations</h6>
                <table class="ractivity-tbl w-100 mb-3">
                    <thead><tr><th>Resignation Date</th><th>Last Working Date</th><th>Notice (days)</th></tr></thead>
                    <tbody>
                        @forelse($employee->resignations as $r)
                            <tr><td>{{ $r->resignation_date?->format('d-m-Y') }}</td><td>{{ $r->last_working_date?->format('d-m-Y') ?? '-' }}</td><td>{{ $r->notice_period_days ?? '-' }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No resignations</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <h6>Terminations</h6>
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Type</th><th>Date</th><th>Reason</th></tr></thead>
                    <tbody>
                        @forelse($employee->terminations as $term)
                            <tr><td>{{ ucfirst($term->type) }}</td><td>{{ $term->termination_date?->format('d-m-Y') }}</td><td>{{ $term->reason ?? '-' }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No terminations</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Attendance -->
            <div class="tab-pane fade" id="tab-attendance">
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Date</th><th>Status</th><th>Check In</th><th>Check Out</th></tr></thead>
                    <tbody>
                        @forelse($employee->attendances as $att)
                            <tr>
                                <td>{{ $att->attendance_date?->format('d-m-Y') }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $att->attendance_status)) }}</td>
                                <td>{{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '-' }}</td>
                                <td>{{ $att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No attendance records (showing latest 30)</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Leave -->
            <div class="tab-pane fade" id="tab-leave">
                <h6>Leave Balances</h6>
                <table class="ractivity-tbl w-100 mb-3">
                    <thead><tr><th>Leave Type</th><th>Year</th><th>Allocated</th><th>Used</th><th>Remaining</th></tr></thead>
                    <tbody>
                        @forelse($employee->leaveBalances as $bal)
                            <tr><td>{{ $bal->leaveType->name ?? '-' }}</td><td>{{ $bal->year }}</td><td>{{ $bal->allocated_days }}</td><td>{{ $bal->used_days }}</td><td>{{ $bal->remaining_days }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No leave balances</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <h6>Leave Requests</h6>
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Leave Type</th><th>Dates</th><th>Days</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($employee->leaveRequests as $lr)
                            <tr><td>{{ $lr->leaveType->name ?? '-' }}</td><td>{{ $lr->start_date?->format('d-m-Y') }} - {{ $lr->end_date?->format('d-m-Y') }}</td><td>{{ $lr->total_days }}</td><td>{{ ucfirst($lr->approval_status) }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No leave requests</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Salary & Payroll -->
            <div class="tab-pane fade" id="tab-payroll">
                <h6>Salary Structures</h6>
                <table class="ractivity-tbl w-100 mb-3">
                    <thead><tr><th>Effective Date</th><th>Basic</th><th>Gross</th></tr></thead>
                    <tbody>
                        @forelse($employee->salaryStructures as $ss)
                            <tr><td>{{ $ss->effective_date?->format('d-m-Y') }}</td><td>{{ number_format($ss->basic_salary, 2) }}</td><td>{{ number_format($ss->gross_salary, 2) }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No salary structures</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <h6>Payroll</h6>
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Period</th><th>Net Salary</th><th>Payment Status</th></tr></thead>
                    <tbody>
                        @forelse($employee->payrolls as $pr)
                            <tr><td>{{ \Carbon\Carbon::create($pr->year, $pr->month, 1)->format('F Y') }}</td><td>{{ number_format($pr->net_salary, 2) }}</td><td>{{ ucfirst($pr->payment_status) }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No payroll records</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Claims & Loans -->
            <div class="tab-pane fade" id="tab-finance">
                <h6>Expense Claims</h6>
                <table class="ractivity-tbl w-100 mb-3">
                    <thead><tr><th>Category</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($employee->expenseClaims as $ec)
                            <tr><td>{{ $ec->category }}</td><td>{{ number_format($ec->amount, 2) }}</td><td>{{ $ec->expense_date?->format('d-m-Y') }}</td><td>{{ ucfirst($ec->approval_status) }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No expense claims</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <h6>Loans</h6>
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Amount</th><th>Remaining</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($employee->employeeLoans as $loan)
                            <tr><td>{{ number_format($loan->loan_amount, 2) }}</td><td>{{ number_format($loan->remaining_balance, 2) }}</td><td>{{ ucfirst($loan->approval_status) }}</td></tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No loans</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Performance -->
            <div class="tab-pane fade" id="tab-reviews">
                <table class="ractivity-tbl w-100">
                    <thead><tr><th>Period</th><th>Reviewer</th><th>Rating</th></tr></thead>
                    <tbody>
                        @forelse($employee->performanceReviews as $rev)
                            <tr><td>{{ $rev->review_period_start?->format('d-m-Y') }} - {{ $rev->review_period_end?->format('d-m-Y') }}</td><td>{{ $rev->reviewer->full_name ?? '-' }}</td><td>{{ number_format($rev->rating, 1) }} / 5</td></tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="text-center py-4">
                                        <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                                        <p class="text-muted mb-0">No performance reviews</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="offcanvas-footer d-flex justify-content-between align-items-center p-3 border-top">
        <button type="button" class="btn-nx-outline" data-bs-dismiss="offcanvas">
            <i class="ri-close-large-line me-1"></i> Cancel
        </button>
    </div>
</form>
