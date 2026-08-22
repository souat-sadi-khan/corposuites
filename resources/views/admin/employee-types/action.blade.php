<div class="tl-actions">
    <button class="tl-icon-btn side-offcanvas" data-url="{{ route('admin.master-details.show', ['type' => 'employee-type', 'id' => $row->id]) }}" data-width="680px" title="Details"><i class="ri-information-line"></i></button>
    <!-- Edit -->
    @if(Auth::guard('admin')->user()?->can('employee-type.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.employee-types.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endif

    <!-- Delete -->
    @if(Auth::guard('admin')->user()?->can('employee-type.delete'))
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.employee-types.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
