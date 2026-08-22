<div class="tl-actions">
    <!-- Edit (disabled: a Promotion record is not meant to be hand-edited after the fact) -->
    {{-- <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.promotions.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button> --}}

    <!-- Delete -->
    @if(Auth::guard('admin')->user()?->can('promotion.delete'))
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.promotions.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
