<div class="tl-actions">
    @if($row->approval_status === 'pending')
        <!-- Approve -->
        <button class="tl-icon-btn" id="approveLeaveRequest" data-url="{{ route('admin.leave-requests.approve', $row->id) }}" title="Approve">
            <i class="ri-check-line text-success"></i>
        </button>

        <!-- Reject -->
        <button class="tl-icon-btn" id="rejectLeaveRequest" data-url="{{ route('admin.leave-requests.reject', $row->id) }}" title="Reject">
            <i class="ri-close-line text-danger"></i>
        </button>
    @endif

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.leave-requests.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.leave-requests.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
