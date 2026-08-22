<div class="tl-actions">
    <!-- View -->
    <button class="tl-icon-btn side-offcanvas" data-url="{{ route('admin.employees.show', $row->id) }}" title="View">
        <i class="ri-eye-line"></i>
    </button>

    <!-- Edit -->
    <button class="tl-icon-btn side-offcanvas" data-url="{{ route('admin.employees.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Create Login -->
    @if(!$row->admin)
        <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.employees.create-login', $row->id) }}" title="Create Login">
            <i class="ri-key-2-line"></i>
        </button>
    @endif

    <!-- Employee records workspace -->
    <div class="d-inline-block employee-records-dropdown">
        <button type="button" class="tl-icon-btn employee-records-trigger" data-bs-toggle="modal" data-bs-target="#employeeRecordsModal{{ $row->id }}" title="Employee records" aria-label="Employee records">
            <i class="ri-layout-grid-line employee-records-chevron"></i>
        </button>
        <div class="modal fade" id="employeeRecordsModal{{ $row->id }}" tabindex="-1" aria-labelledby="employeeRecordsModalLabel{{ $row->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="nx-modal-box fm-modal-content employee-records-modal">
                    <div class="modal-header fm-modal-head employee-records-menu-head">
                        <div>
                            <h5 class="modal-title" id="employeeRecordsModalLabel{{ $row->id }}"><i class="ri-folder-user-line me-1"></i> Employee records</h5>
                            <p>{{ $row->full_name }} &middot; {{ $row->employee_code }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body fm-modal-body employee-records-modal-body">
                        <p class="employee-records-intro">Choose a record area to view or manage information for this employee.</p>
                        <div class="employee-records-grid">
                <div class="employee-records-group">
                    <span class="employee-records-group-title">Personal</span>
                    <a class="dropdown-item" href="{{ route('admin.employee-documents.index', ['employee_id' => $row->id]) }}"><i class="ri-file-user-line"></i> Documents</a>
                    <a class="dropdown-item" href="{{ route('admin.emergency-contacts.index', ['employee_id' => $row->id]) }}"><i class="ri-contacts-line"></i> Emergency contacts</a>
                    <a class="dropdown-item" href="{{ route('admin.bank-accounts.index', ['employee_id' => $row->id]) }}"><i class="ri-bank-line"></i> Bank accounts</a>
                    <a class="dropdown-item" href="{{ route('admin.educations.index', ['employee_id' => $row->id]) }}"><i class="ri-graduation-cap-line"></i> Education</a>
                    <a class="dropdown-item" href="{{ route('admin.experiences.index', ['employee_id' => $row->id]) }}"><i class="ri-briefcase-line"></i> Experience</a>
                </div>
                <div class="employee-records-group">
                    <span class="employee-records-group-title">Employment</span>
                    <a class="dropdown-item" href="{{ route('admin.transfers.index', ['employee_id' => $row->id]) }}"><i class="ri-shuffle-line"></i> Transfers</a>
                    <a class="dropdown-item" href="{{ route('admin.promotions.index', ['employee_id' => $row->id]) }}"><i class="ri-arrow-up-circle-line"></i> Promotions</a>
                    <a class="dropdown-item" href="{{ route('admin.resignations.index', ['employee_id' => $row->id]) }}"><i class="ri-logout-box-line"></i> Resignations</a>
                    <a class="dropdown-item" href="{{ route('admin.terminations.index', ['employee_id' => $row->id]) }}"><i class="ri-user-unfollow-line"></i> Terminations</a>
                    <a class="dropdown-item" href="{{ route('admin.performance-reviews.index', ['employee_id' => $row->id]) }}"><i class="ri-line-chart-line"></i> Performance reviews</a>
                </div>
                <div class="employee-records-group">
                    <span class="employee-records-group-title">Time & leave</span>
                    <a class="dropdown-item" href="{{ route('admin.attendances.index', ['employee_id' => $row->id]) }}"><i class="ri-fingerprint-line"></i> Attendance</a>
                    <a class="dropdown-item" href="{{ route('admin.attendance-adjustments.index', ['employee_id' => $row->id]) }}"><i class="ri-time-zone-line"></i> Adjustments</a>
                    <a class="dropdown-item" href="{{ route('admin.leave-balances.index', ['employee_id' => $row->id]) }}"><i class="ri-donut-chart-line"></i> Leave balances</a>
                    <a class="dropdown-item" href="{{ route('admin.leave-requests.index', ['employee_id' => $row->id]) }}"><i class="ri-mail-send-line"></i> Leave requests</a>
                </div>
                <div class="employee-records-group">
                    <span class="employee-records-group-title">Pay & finance</span>
                    <a class="dropdown-item" href="{{ route('admin.salary-structures.index', ['employee_id' => $row->id]) }}"><i class="ri-file-list-line"></i> Salary structure</a>
                    <a class="dropdown-item" href="{{ route('admin.payrolls.index', ['employee_id' => $row->id]) }}"><i class="ri-hand-coin-line"></i> Payroll</a>
                    <a class="dropdown-item" href="javascript:void(0)" id="openModal" data-url="{{ route('admin.employees.salary-certificate-form', $row->id) }}"><i class="ri-award-line"></i> Salary certificate</a>
                    <a class="dropdown-item" href="{{ route('admin.expense-claims.index', ['employee_id' => $row->id]) }}"><i class="ri-receipt-line"></i> Expense claims</a>
                    <a class="dropdown-item" href="{{ route('admin.employee-loans.index', ['employee_id' => $row->id]) }}"><i class="ri-safe-2-line"></i> Employee loans</a>
                </div>
                        </div>
                    </div>
                    <div class="modal-footer fm-modal-foot">
                        <span class="fm-foot-note"><i class="ri-information-line"></i> Opens the selected employee record area.</span>
                        <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.employees.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
