<div class="tl-actions">
    <!-- Preview -->
    <button class="tl-icon-btn" id="openModal" data-width="70%" data-url="{{ route('admin.email.email-templates.preview', $row->id) }}" title="Preview">
        <i class="ri-eye-line"></i>
    </button>

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.email.email-templates.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Duplicate -->
    <button class="tl-icon-btn" id="duplicate_item" data-id="{{ $row->id }}" data-url="{{ route('admin.email.email-templates.duplicate', $row->id) }}" title="Duplicate">
        <i class="ri-file-copy-line"></i>
    </button>

    <!-- Delete (only if not system) -->
    @if(!$row->is_system)
        <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.email.email-templates.destroy', $row->id) }}" data-del="1" title="Delete">
            <i class="ri-delete-bin-line"></i>
        </button>
    @endif
</div>
