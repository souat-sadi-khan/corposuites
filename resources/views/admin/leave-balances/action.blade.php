<div class="tl-actions">
    <!-- Edit -->
    @can('leave-balance.edit')
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.leave-balances.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endcan

    @can('leave-balance.encash')
    @if(optional($row->leaveType)->is_encashable && $row->remaining_days > 0)
        <!-- Encash -->
        <button class="tl-icon-btn success encash-balance"
                data-url="{{ route('admin.leave-balances.encash', $row->id) }}"
                data-remaining="{{ $row->remaining_days }}"
                title="Encash remaining balance">
            <i class="ri-money-dollar-circle-line"></i>
        </button>
    @endif
    @endcan

    <!-- Delete -->
    @can('leave-balance.delete')
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.leave-balances.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endcan
</div>
