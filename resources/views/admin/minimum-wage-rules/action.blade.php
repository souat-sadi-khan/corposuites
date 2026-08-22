<div class="tl-actions">
    @if(Auth::guard('admin')->user()?->can('minimum-wage-rule.edit'))
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.minimum-wage-rules.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    @endif

    @if(Auth::guard('admin')->user()?->can('minimum-wage-rule.delete'))
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.minimum-wage-rules.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
    @endif
</div>
