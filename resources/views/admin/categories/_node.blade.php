@foreach($categories->where('parent_id', $parentId) as $category)
    @php $children = $categories->where('parent_id', $category->id); @endphp
    <li class="cat-node" data-name="{{ strtolower($category->name) }}">
        <div class="cat-node-row">
            <span class="cat-toggle {{ $children->isEmpty() ? 'leaf' : '' }}">
                <i class="ri-arrow-right-s-line"></i>
            </span>
            <span class="cat-name">{{ $category->name }}</span>
            @if($category->description)
                <span class="cat-desc">{{ $category->description }}</span>
            @endif
            <div class="fm-field mb-0">
                <div class="form-check form-switch mb-0">
                    <input data-url="{{ route('admin.categories.status', $category->id) }}" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status{{ $category->id }}" {{ $category->status ? 'checked' : '' }} data-id="{{ $category->id }}">
                </div>
            </div>
            <div class="cat-actions">
                <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.categories.create', ['parent_id' => $category->id]) }}" title="Add Subcategory">
                    <i class="ri-add-line"></i>
                </button>
                <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.categories.edit', $category->id) }}" title="Edit">
                    <i class="ri-pencil-line"></i>
                </button>
                <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $category->id }}" data-url="{{ route('admin.categories.destroy', $category->id) }}" data-del="1" title="Delete">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>

        @if($children->isNotEmpty())
            <ul class="cat-children">
                @include('admin.categories._node', ['categories' => $categories, 'parentId' => $category->id])
            </ul>
        @endif
    </li>
@endforeach
