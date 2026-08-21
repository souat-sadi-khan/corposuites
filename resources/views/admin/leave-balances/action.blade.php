<div class="tl-actions">
    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.leave-balances.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    @if(optional($row->leaveType)->is_encashable && $row->remaining_days > 0)
        <!-- Encash -->
        <button class="tl-icon-btn success encash-balance"
                data-url="{{ route('admin.leave-balances.encash', $row->id) }}"
                data-remaining="{{ $row->remaining_days }}"
                title="Encash remaining balance">
            <i class="ri-money-dollar-circle-line"></i>
        </button>
    @endif

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.leave-balances.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
