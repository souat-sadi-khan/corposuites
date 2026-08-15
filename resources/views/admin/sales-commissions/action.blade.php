<div class="tl-actions">
    @if($row->payment_status !== 'paid')
        <!-- Mark as Paid -->
        <button class="tl-icon-btn success sales-commission-mark-paid-btn" data-id="{{ $row->id }}" data-url="{{ route('admin.sales-commissions.mark-paid', $row->id) }}" title="Mark as Paid">
            <i class="ri-check-double-line"></i>
        </button>

        <!-- Edit -->
        <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.sales-commissions.edit', $row->id) }}" title="Edit">
            <i class="ri-pencil-line"></i>
        </button>
    @endif

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.sales-commissions.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
