<div class="tl-actions">
    <!-- Manage Statuses -->
    <a class="tl-icon-btn" href="{{ route('admin.workflow-statuses.index', ['workflow_definition_id' => $row->id]) }}" title="Manage Statuses">
        <i class="ri-flag-2-line"></i>
    </a>

    <!-- Manage Notifications -->
    <a class="tl-icon-btn" href="{{ route('admin.workflow-notification-triggers.index', ['workflow_definition_id' => $row->id]) }}" title="Manage Notifications">
        <i class="ri-notification-3-line"></i>
    </a>

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.workflow-definitions.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.workflow-definitions.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
