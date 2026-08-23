<div class="tl-actions">
    @php $isSelfService = (bool) optional(auth()->guard('admin')->user())->employee_id; @endphp

    <!-- View Details (full request + approval workflow progress) -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.leave-requests.details', $row->id) }}" title="View Details">
        <i class="ri-eye-line"></i>
    </button>

    @if($row->approval_status === 'pending' && !$isSelfService)
        <!-- Approve -->
        @if(Auth::guard('admin')->user()?->can('leave-request.approve'))
        <button class="tl-icon-btn" id="approveLeaveRequest" data-url="{{ route('admin.leave-requests.approve', $row->id) }}" title="Approve">
            <i class="ri-check-line text-success"></i>
        </button>
        @endif

        <!-- Reject -->
        @if(Auth::guard('admin')->user()?->can('leave-request.reject'))
        <button class="tl-icon-btn" id="rejectLeaveRequest" data-url="{{ route('admin.leave-requests.reject', $row->id) }}" title="Reject">
            <i class="ri-close-line text-danger"></i>
        </button>
        @endif
    @endif

    @if(in_array($row->approval_status, ['pending', 'approved']))
        <!-- Cancel (refunds balance if approved) -->
        @if(Auth::guard('admin')->user()?->can('leave-request.cancel'))
        <button class="tl-icon-btn" id="cancelLeaveRequest" data-url="{{ route('admin.leave-requests.cancel', $row->id) }}" title="Cancel">
            <i class="ri-forbid-line text-warning"></i>
        </button>
        @endif
    @endif

    @if($row->attachment)
        <!-- View attachment -->
        <a class="tl-icon-btn" href="{{ asset($row->attachment) }}" target="_blank" title="View attachment">
            <i class="ri-attachment-2"></i>
        </a>
    @endif

    <!-- Edit -->
    @if(Auth::guard('admin')->user()?->can('leave-request.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.leave-requests.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endif

    <!-- Delete -->
    @if(Auth::guard('admin')->user()?->can('leave-request.delete'))
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.leave-requests.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
