<div class="tl-actions">
    <!-- Assign to Employees -->
    @can('salary-template.assign')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.salary-templates.assign-form', $row->id) }}" title="Assign to Employees">
        <i class="ri-group-line text-primary"></i>
    </button>
    @endcan

    <!-- Edit -->
    @can('salary-template.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.salary-templates.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    <!-- Delete -->
    @can('salary-template.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.salary-templates.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
