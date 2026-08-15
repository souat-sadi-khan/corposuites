<div class="tl-actions">
    @if (in_array($row->timesheet_status, ['draft', 'rejected']))
        <button class="tl-icon-btn timesheet-regenerate-btn" data-url="{{ route('admin.project-timesheets.regenerate', $row->id) }}" title="Regenerate — re-pull this week's entries">
            <i class="ri-refresh-line"></i>
        </button>
    @endif

    @if ($row->timesheet_status === 'draft' && $row->total_hours > 0)
        <button class="tl-icon-btn text-primary timesheet-submit-btn" data-url="{{ route('admin.project-timesheets.submit', $row->id) }}" title="Submit for approval">
            <i class="ri-send-plane-line"></i>
        </button>
    @endif

    @if ($row->timesheet_status === 'submitted')
        <button class="tl-icon-btn text-success timesheet-approve-btn" data-url="{{ route('admin.project-timesheets.approve', $row->id) }}" title="Approve">
            <i class="ri-checkbox-circle-line"></i>
        </button>
        <button class="tl-icon-btn text-danger timesheet-reject-btn" data-url="{{ route('admin.project-timesheets.reject', $row->id) }}" title="Reject">
            <i class="ri-close-circle-line"></i>
        </button>
    @endif

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.project-timesheets.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.project-timesheets.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
