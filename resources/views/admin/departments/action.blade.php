<div class="tl-actions">
    <button class="tl-icon-btn side-offcanvas" data-url="{{ route('admin.master-details.show', ['type' => 'department', 'id' => $row->id]) }}" data-width="680px" title="Details"><i class="ri-information-line"></i></button>
    @if(Auth::guard('admin')->user()?->can('department.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.departments.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endif
    @if(Auth::guard('admin')->user()?->can('department.delete'))
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.departments.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
