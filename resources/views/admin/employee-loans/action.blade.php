<div class="tl-actions">
    @if($row->approval_status === 'pending')
        <!-- Approve -->
        <button class="tl-icon-btn" id="approveEmployeeLoan" data-url="{{ route('admin.employee-loans.approve', $row->id) }}" title="Approve">
            <i class="ri-check-line text-success"></i>
        </button>

        <!-- Reject -->
        <button class="tl-icon-btn" id="rejectEmployeeLoan" data-url="{{ route('admin.employee-loans.reject', $row->id) }}" title="Reject">
            <i class="ri-close-line text-danger"></i>
        </button>
    @elseif($row->approval_status === 'approved' && $row->remaining_balance > 0)
        <!-- Record Payment -->
        <button class="tl-icon-btn" id="recordLoanPayment" data-url="{{ route('admin.employee-loans.record-payment', $row->id) }}" title="Record Payment">
            <i class="ri-money-dollar-circle-line text-success"></i>
        </button>
    @endif

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.employee-loans.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.employee-loans.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
