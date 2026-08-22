<div class="tl-actions">
    @php $isSelfService = (bool) optional(auth()->guard('admin')->user())->employee_id; @endphp
    @if($row->approval_status === 'pending' && !$isSelfService)
        <!-- Approve -->
        @can('leave-request.approve')
        <button class="tl-icon-btn" id="approveLeaveRequest" data-url="{{ route('admin.leave-requests.approve', $row->id) }}" title="Approve">
            <i class="ri-check-line text-success"></i>
        </button>
        @endcan

        <!-- Reject -->
        @can('leave-request.reject')
        <button class="tl-icon-btn" id="rejectLeaveRequest" data-url="{{ route('admin.leave-requests.reject', $row->id) }}" title="Reject">
            <i class="ri-close-line text-danger"></i>
        </button>
        @endcan
    @endif

    @if(in_array($row->approval_status, ['pending', 'approved']))
        <!-- Cancel (refunds balance if approved) -->
        @can('leave-request.cancel')
        <button class="tl-icon-btn" id="cancelLeaveRequest" data-url="{{ route('admin.leave-requests.cancel', $row->id) }}" title="Cancel">
            <i class="ri-forbid-line text-warning"></i>
        </button>
        @endcan
    @endif

    @if($row->attachment)
        <!-- View attachment -->
        <a class="tl-icon-btn" href="{{ asset($row->attachment) }}" target="_blank" title="View attachment">
            <i class="ri-attachment-2"></i>
        </a>
    @endif

    <!-- Edit -->
    @can('leave-request.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.leave-requests.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    <!-- Delete -->
    @can('leave-request.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.leave-requests.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
