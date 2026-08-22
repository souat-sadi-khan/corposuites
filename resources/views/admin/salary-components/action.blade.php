<div class="tl-actions">
    <button class="tl-icon-btn side-offcanvas" data-url="{{ route('admin.salary-components.details',$row) }}" data-width="800px" title="Component history"><i class="ri-information-line"></i></button>

    <!-- Bulk Assign to Employees -->
    @if(Auth::guard('admin')->user()?->can('salary-component.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.salary-components.bulk-assign-form', $row->id) }}" title="Bulk assign to employees">
        <i class="ri-group-line text-primary"></i>
    </button>
    @endif

    <!-- Edit -->
    @if(Auth::guard('admin')->user()?->can('salary-component.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.salary-components.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endif

    <!-- Delete -->
    @if(Auth::guard('admin')->user()?->can('salary-component.delete'))
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.salary-components.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
