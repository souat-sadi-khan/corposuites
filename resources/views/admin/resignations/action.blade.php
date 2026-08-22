<div class="tl-actions">
    <!-- Edit -->
    @if(Auth::guard('admin')->user()?->can('resignation.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.resignations.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endif

    <!-- Delete -->
    @if(Auth::guard('admin')->user()?->can('resignation.delete'))
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.resignations.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
