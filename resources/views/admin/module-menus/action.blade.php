<div class="tl-actions">
    <!-- Edit (opens offcanvas) -->
    <button class="tl-icon-btn side-offcanvas" data-url="{{ route('admin.module-menus.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.module-menus.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
