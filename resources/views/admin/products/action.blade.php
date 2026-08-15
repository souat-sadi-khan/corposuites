<div class="tl-actions">
    <!-- Manage Images -->
    <a class="tl-icon-btn" href="{{ route('admin.product-images.index', ['product_id' => $row->id]) }}" title="Manage Images">
        <i class="ri-image-line"></i>
    </a>

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.products.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.products.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
