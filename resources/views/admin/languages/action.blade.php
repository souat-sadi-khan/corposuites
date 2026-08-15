<div class="tl-actions">
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.languages.edit', $model->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>
    <a class="tl-icon-btn" href="{{ route('admin.languages.translations', $model->id) }}" title="Translation">
        <i class="ri-translate-2"></i>
    </a>
    <button class="tl-icon-btn danger" id="delete_item" data-id ="{{ $model->id }}" data-url="{{ route('admin.languages.destroy',$model->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
