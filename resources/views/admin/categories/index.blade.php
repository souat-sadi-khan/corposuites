@extends('admin.layout.app', ['title' => 'Categories', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="categorySearch" placeholder="Search Categories">
        </div>

        <div class="tl-spacer"></div>

        <button type="button" class="btn-nx-outline" id="expandAll">
            <i class="ri-expand-diagonal-line"></i>
            Expand All
        </button>
        <button type="button" class="btn-nx-outline" id="collapseAll">
            <i class="ri-collapse-diagonal-line"></i>
            Collapse All
        </button>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.categories.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Category
        </button>
    </div>

    <!-- Tree Card -->
    <div class="nx-card tl-card">
        <div class="cat-tree" id="categoryTree">
            @if($categories->where('parent_id', null)->isEmpty())
                <div class="text-center py-4">
                    <img src="{{ asset('assets/images/nothing-to-show.png') }}" class="img-fluid mb-2" style="max-width:150px">
                    <p class="text-muted mb-0">No categories available</p>
                </div>
            @else
                <ul class="cat-tree-root">
                    @include('admin.categories._node', ['categories' => $categories, 'parentId' => null])
                </ul>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .cat-tree ul {
            list-style: none;
            margin: 0;
            padding-left: 1.25rem;
        }
        .cat-tree-root {
            padding-left: 0 !important;
        }
        .cat-node {
            padding: 0.4rem 0;
        }
        .cat-node-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .cat-toggle {
            width: 20px;
            cursor: pointer;
            text-align: center;
            color: #888;
        }
        .cat-toggle.leaf {
            visibility: hidden;
        }
        .cat-name {
            font-weight: 600;
        }
        .cat-desc {
            color: #888;
            font-size: 0.8rem;
        }
        .cat-actions {
            margin-left: auto;
            display: flex;
            gap: 0.25rem;
        }
        .cat-children.collapsed {
            display: none;
        }
        .cat-node.hidden-by-search {
            display: none;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('assets/system/js/pages/categories.js') }}"></script>
@endpush
