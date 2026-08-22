<div class="tl-actions">
    @if($row->approval_status === 'pending')
        <!-- Approve -->
        @can('employee-loan.approve')
        <button class="tl-icon-btn" id="approveEmployeeLoan" data-url="{{ route('admin.employee-loans.approve', $row->id) }}" title="Approve">
            <i class="ri-check-line text-success"></i>
        </button>
        @endcan

        <!-- Reject -->
        @can('employee-loan.reject')
        <button class="tl-icon-btn" id="rejectEmployeeLoan" data-url="{{ route('admin.employee-loans.reject', $row->id) }}" title="Reject">
            <i class="ri-close-line text-danger"></i>
        </button>
        @endcan
    @elseif($row->approval_status === 'approved' && $row->remaining_balance > 0)
        <!-- Record Payment -->
        @can('employee-loan.record-payment')
        <button class="tl-icon-btn" id="recordLoanPayment" data-url="{{ route('admin.employee-loans.record-payment', $row->id) }}" title="Record Payment">
            <i class="ri-money-dollar-circle-line text-success"></i>
        </button>
        @endcan
    @endif

    <!-- Edit -->
    @can('employee-loan.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.employee-loans.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    <!-- Delete -->
    @can('employee-loan.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.employee-loans.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
