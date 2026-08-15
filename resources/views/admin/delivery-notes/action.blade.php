<div class="tl-actions">
    <!-- Print -->
    <a class="tl-icon-btn" href="{{ route('admin.delivery-notes.print', $row->id) }}" target="_blank" title="Print">
        <i class="ri-printer-line"></i>
    </a>

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.delivery-notes.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.delivery-notes.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
