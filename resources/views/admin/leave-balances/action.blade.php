<div class="tl-actions">
    <!-- Manage (the full per-leave-type breakdown for this employee/year) -->
    @if(Auth::guard('admin')->user()?->can('leave-balance.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.leave-balances.manage.edit', [$employeeId, $year]) }}" title="Manage ({{ $typesCount }} leave type{{ $typesCount === 1 ? '' : 's' }})">
        <i class="ri-list-settings-line"></i>
    </button>
    @endif

    <!-- Delete the whole employee+year record (every leave type under it) -->
    @if(Auth::guard('admin')->user()?->can('leave-balance.delete'))
    <button class="tl-icon-btn danger" id="delete_item"
            data-id="{{ $employeeId }}-{{ $year }}"
            data-url="{{ route('admin.leave-balances.group.destroy', [$employeeId, $year]) }}"
            data-del="1"
            title="Delete Record ({{ $typesCount }} leave type{{ $typesCount === 1 ? '' : 's' }})">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
