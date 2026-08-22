<div class="tl-actions">
    <!-- Edit -->
    @can('performance-review.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.performance-reviews.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    <!-- Delete -->
    @can('performance-review.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.performance-reviews.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
