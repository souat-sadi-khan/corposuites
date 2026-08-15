<div class="tl-actions">
    @if ($row->approval_status === 'pending')
        <button class="tl-icon-btn text-success expense-approve-btn" data-url="{{ route('admin.project-expenses.approve', $row->id) }}" title="Approve">
            <i class="ri-checkbox-circle-line"></i>
        </button>
        <button class="tl-icon-btn text-danger expense-reject-btn" data-url="{{ route('admin.project-expenses.reject', $row->id) }}" title="Reject">
            <i class="ri-close-circle-line"></i>
        </button>
    @endif

    @if ($row->approval_status !== 'approved')
        <!-- Edit -->
        <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.project-expenses.edit', $row->id) }}" title="Edit">
            <i class="ri-pencil-line"></i>
        </button>
    @else
        <span class="tl-icon-btn disabled" title="Approved — locked from editing">
            <i class="ri-lock-line"></i>
        </span>
    @endif

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.project-expenses.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
