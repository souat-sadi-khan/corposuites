<div class="tl-actions">
    <!-- Edit -->
    @can('bank-account.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.bank-accounts.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    <!-- Delete -->
    @can('bank-account.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.bank-accounts.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
