<div class="tl-actions">

    <!-- View Logs -->
    <a href="{{ route('admin.email.providers.logs', $row->provider_id) }}" class="tl-icon-btn" title="View Logs">
        <i class="ri-file-list-line"></i>
    </a>

    <!-- Send Test Email -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.email.sender-identities.test-email',  $row->id) }}" data-id="{{ $row->id }}" title="Send Test Email">
        <i class="ri-send-ins-line"></i>
    </button>

    @if(!$row->is_default)
        <button class="tl-icon-btn set-default-sender" data-id="{{ $row->id }}" data-provider="{{ $row->provider_id }}" title="Set as Default">
            <i class="ri-star-line"></i>
        </button>
    @else
        <span class="tl-icon-btn disabled" title="Default Sender">
            <i class="ri-star-fill text-warning"></i>
        </span>
    @endif

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.email.sender-identities.edit',  $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-url="{{ route('admin.email.sender-identities.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
