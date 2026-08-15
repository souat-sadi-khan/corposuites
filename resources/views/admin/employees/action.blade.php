<div class="tl-actions">
    <!-- View -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.employees.show', $row->id) }}" title="View">
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

    <!-- Related Records -->
    <div class="dropdown d-inline-block">
        <button class="tl-icon-btn dropdown-toggle-nocaret" data-bs-toggle="dropdown" aria-expanded="false" title="View Records">
            <i class="ri-folder-user-line"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item" href="{{ route('admin.employee-documents.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-file-user-line me-2"></i> Documents
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.emergency-contacts.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-contacts-line me-2"></i> Emergency Contacts
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.bank-accounts.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-bank-line me-2"></i> Bank Accounts
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.educations.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-graduation-cap-line me-2"></i> Education
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.experiences.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-briefcase-line me-2"></i> Experience
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.transfers.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-shuffle-line me-2"></i> Transfers
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.promotions.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-arrow-up-circle-line me-2"></i> Promotions
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.resignations.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-logout-box-line me-2"></i> Resignations
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.terminations.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-user-unfollow-line me-2"></i> Terminations
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.attendances.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-fingerprint-line me-2"></i> Attendance
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.attendance-adjustments.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-time-zone-line me-2"></i> Attendance Adjustments
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.leave-balances.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-donut-chart-line me-2"></i> Leave Balances
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.leave-requests.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-mail-send-line me-2"></i> Leave Requests
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.salary-structures.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-file-list-line me-2"></i> Salary Structure
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.payrolls.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-hand-coin-line me-2"></i> Payroll
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.expense-claims.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-receipt-line me-2"></i> Expense Claims
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.employee-loans.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-safe-2-line me-2"></i> Employee Loans
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('admin.performance-reviews.index', ['employee_id' => $row->id]) }}">
                    <i class="ri-line-chart-line me-2"></i> Performance Reviews
                </a>
            </li>
        </ul>
    </div>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.employees.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
