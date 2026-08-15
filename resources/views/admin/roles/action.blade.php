@if ($model->id != 1)
    <div class="tl-actions">
        <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.roles.edit', $model->id) }}" title="Edit">
            <i class="ri-pencil-line"></i>
        </button>
        <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.roles.show', $model->id) }}" title="View Permission">
            <i class="ri-eye-line"></i>
        </button>
        <button class="tl-icon-btn side-offcanvas" data-url="{{ route('admin.roles.assign', $model->id) }}" title="Assign Role">
            <i class="ri-user-settings-line"></i>
        </button>
        <button class="tl-icon-btn danger" id="delete_item" data-id ="{{ $model->id }}" data-url="{{ route('admin.roles.destroy',$model->id) }}" data-del="1" title="Delete">
            <i class="ri-delete-bin-line"></i>
        </button>
    </div>
@endif

{{-- <div class="dropdown">
    <button class="dropdown-toggle-nocaret wh-35 p-0 rounded-circle dropdown-toggle btn btn-light" data-bs-toggle="dropdown">
        <i class="bx bx-dots-vertical-rounded fs-5 lh-0"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @if(Auth::guard('admin')->user()->hasPermissionTo('role.edit'))
            <li>
                <a href="javascript:;"  class="side-offcanvas dropdown-item">
                    <i class="bx bx-edit-alt me-2"></i>
                    Edit
                </a>
            </li>
        @endif

        @if(Auth::guard('admin')->user()->hasPermissionTo('role.delete'))
            <li>
                <a href="javascript:;"  class="dropdown-item text-danger">
                    <i class="bx bx-trash me-2"></i>
                    Delete
                </a>
            </li>
        @endif
    </ul>
</div> --}}
