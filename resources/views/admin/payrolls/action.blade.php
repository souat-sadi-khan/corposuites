<div class="tl-actions">
    @if($row->payment_status !== 'paid')
        <!-- Mark as Paid -->
        <button class="tl-icon-btn" id="markPaid" data-url="{{ route('admin.payrolls.mark-paid', $row->id) }}" title="Mark as Paid">
            <i class="ri-bank-card-line text-success"></i>
        </button>
    @endif

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.payrolls.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
