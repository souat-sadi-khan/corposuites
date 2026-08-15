<div class="tl-actions">
    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.modules.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Install / Uninstall -->
    @if($row->installed_at)
        <button class="tl-icon-btn" id="toggleInstall" data-url="{{ route('admin.modules.uninstall', $row->id) }}" data-method="POST" title="Uninstall">
            <i class="ri-download-2-line text-danger"></i>
        </button>
    @else
        <button class="tl-icon-btn" id="toggleInstall" data-url="{{ route('admin.modules.install', $row->id) }}" data-method="POST" title="Install">
            <i class="ri-download-2-line text-primary"></i>
        </button>
    @endif

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.modules.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
