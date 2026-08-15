<div class="tl-actions">
    <!-- Receipt -->
    <a class="tl-icon-btn" href="{{ route('admin.pos.receipt', $row->id) }}" target="_blank" title="Print Receipt">
        <i class="ri-printer-line"></i>
    </a>

    @if($row->pos_status !== 'voided')
        <!-- Void -->
        <button class="tl-icon-btn danger pos-void-btn" data-id="{{ $row->id }}" data-url="{{ route('admin.pos.void', $row->id) }}" title="Void Sale">
            <i class="ri-close-circle-line"></i>
        </button>
    @endif

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.pos.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
