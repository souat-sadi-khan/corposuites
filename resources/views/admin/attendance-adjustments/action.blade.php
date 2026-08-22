<div class="tl-actions">
    @if($row->approval_status === 'pending')
        <!-- Approve -->
        @can('attendance-adjustment.approve')
        <button class="tl-icon-btn" id="approveAdjustment" data-url="{{ route('admin.attendance-adjustments.approve', $row->id) }}" title="Approve">
            <i class="ri-check-line text-success"></i>
        </button>
        @endcan

        <!-- Reject -->
        @can('attendance-adjustment.reject')
        <button class="tl-icon-btn" id="rejectAdjustment" data-url="{{ route('admin.attendance-adjustments.reject', $row->id) }}" title="Reject">
            <i class="ri-close-line text-danger"></i>
        </button>
        @endcan
    @endif

    <!-- Edit -->
    @can('attendance-adjustment.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.attendance-adjustments.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    <!-- Delete -->
    @can('attendance-adjustment.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.attendance-adjustments.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
