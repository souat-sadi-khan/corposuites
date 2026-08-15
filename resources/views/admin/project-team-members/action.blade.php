<div class="tl-actions">
    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.project-team-members.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.project-team-members.destroy', $row->id) }}" data-del="1" title="Remove">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
