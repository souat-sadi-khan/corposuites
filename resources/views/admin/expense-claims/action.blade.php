<div class="tl-actions">
    @if($row->approval_status === 'pending')
        <!-- Approve -->
        @if(Auth::guard('admin')->user()?->can('expense-claim.approve'))
        <button class="tl-icon-btn" id="approveExpenseClaim" data-url="{{ route('admin.expense-claims.approve', $row->id) }}" title="Approve">
            <i class="ri-check-line text-success"></i>
        </button>
        @endif

        <!-- Reject -->
        @if(Auth::guard('admin')->user()?->can('expense-claim.reject'))
        <button class="tl-icon-btn" id="rejectExpenseClaim" data-url="{{ route('admin.expense-claims.reject', $row->id) }}" title="Reject">
            <i class="ri-close-line text-danger"></i>
        </button>
        @endif
    @endif

    <!-- Mark Reimbursed -->
    @if($row->approval_status === 'approved' && $row->payment_status === 'unpaid' && Auth::guard('admin')->user()?->can('expense-claim.approve'))
    <button class="tl-icon-btn" id="markReimbursedExpenseClaim" data-url="{{ route('admin.expense-claims.mark-reimbursed', $row->id) }}" title="Mark Reimbursed">
        <i class="ri-bank-card-line text-primary"></i>
    </button>
    @endif

    <!-- Edit -->
    @if(Auth::guard('admin')->user()?->can('expense-claim.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.expense-claims.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endif

    <!-- Delete -->
    @if(Auth::guard('admin')->user()?->can('expense-claim.delete'))
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.expense-claims.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
