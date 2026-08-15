<div class="tl-actions">
    <!-- Test Connection -->
    <button class="tl-icon-btn test-connection" data-id="{{ $row->id }}" title="Test Connection">
        <i class="ri-wifi-line"></i>
    </button>

    <!-- View Logs -->
    <a href="{{ route('admin.email.providers.logs', $row->id) }}" class="tl-icon-btn" title="View Logs">
        <i class="ri-file-list-line"></i>
    </a>

    <!-- Set Default -->
    @if(!$row->is_default)
        <button class="tl-icon-btn set-default" data-id="{{ $row->id }}" title="Set as Default">
            <i class="ri-star-line"></i>
        </button>
    @else
        <span class="tl-icon-btn disabled" title="Default Provider">
            <i class="ri-star-fill text-warning"></i>
        </span>
    @endif

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.email.providers.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    @if(!$row->is_default)
        <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.email.providers.destroy', $row->id) }}" data-del="1" title="Delete">
            <i class="ri-delete-bin-line"></i>
        </button>
    @endif
</div>
