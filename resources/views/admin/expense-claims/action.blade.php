<div class="tl-actions">
    @if($row->approval_status === 'pending')
        <!-- Approve -->
        @can('expense-claim.approve')
        <button class="tl-icon-btn" id="approveExpenseClaim" data-url="{{ route('admin.expense-claims.approve', $row->id) }}" title="Approve">
            <i class="ri-check-line text-success"></i>
        </button>
        @endcan

        <!-- Reject -->
        @can('expense-claim.reject')
        <button class="tl-icon-btn" id="rejectExpenseClaim" data-url="{{ route('admin.expense-claims.reject', $row->id) }}" title="Reject">
            <i class="ri-close-line text-danger"></i>
        </button>
        @endcan
    @endif

    <!-- Edit -->
    @can('expense-claim.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.expense-claims.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    <!-- Delete -->
    @can('expense-claim.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.expense-claims.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
