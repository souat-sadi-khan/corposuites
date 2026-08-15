<div class="tl-actions">
    @if ($row->is_running)
        <button class="tl-icon-btn text-danger time-entry-stop-btn" data-url="{{ route('admin.project-time-entries.stop-timer', $row->id) }}" title="Stop Timer">
            <i class="ri-stop-circle-line"></i>
        </button>
    @endif

    @if ($row->is_locked)
        <span class="tl-icon-btn disabled" title="Locked — part of a submitted timesheet">
            <i class="ri-lock-line"></i>
        </span>
    @else
        <!-- Edit -->
        <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.project-time-entries.edit', $row->id) }}" title="Edit">
            <i class="ri-pencil-line"></i>
        </button>

        <!-- Delete -->
        <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.project-time-entries.destroy', $row->id) }}" data-del="1" title="Delete">
            <i class="ri-delete-bin-line"></i>
        </button>
    @endif
</div>
