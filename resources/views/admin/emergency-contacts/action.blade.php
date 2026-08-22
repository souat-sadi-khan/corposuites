<div class="tl-actions">
    <!-- Edit -->
    @can('emergency-contact.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.emergency-contacts.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    <!-- Delete -->
    @can('emergency-contact.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.emergency-contacts.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
