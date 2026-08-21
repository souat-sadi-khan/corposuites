@extends('admin.layout.app', ['title' => 'Chart of Accounts', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="chartOfAccountSearch" placeholder="Search Accounts">
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
        <button id="openModal" data-url="{{ route('admin.chart-of-accounts.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Account
        </button>
    </div>

    <!-- Tree Card -->
    <div class="nx-card tl-card">
        <div class="coa-tree" id="chartOfAccountTree">
            @if($accounts->where('parent_id', null)->isEmpty())
                <div class="text-center py-4">
                    <img src="{{ asset('assets/images/nothing-to-show.svg') }}" class="img-fluid mb-2" style="max-width:150px">
                    <p class="text-muted mb-0">No accounts available</p>
                </div>
            @else
                <ul class="coa-tree-root">
                    @include('admin.chart-of-accounts._node', ['accounts' => $accounts, 'parentId' => null])
                </ul>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .coa-tree ul {
            list-style: none;
            margin: 0;
            padding-left: 1.25rem;
        }
        .coa-tree-root {
            padding-left: 0 !important;
        }
        .coa-node {
            padding: 0.4rem 0;
        }
        .coa-node-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .coa-toggle {
            width: 20px;
            cursor: pointer;
            text-align: center;
            color: #888;
        }
        .coa-toggle.leaf {
            visibility: hidden;
        }
        .coa-code {
            font-weight: 600;
            color: #888;
        }
        .coa-name {
            font-weight: 600;
        }
        .coa-actions {
            margin-left: auto;
            display: flex;
            gap: 0.25rem;
        }
        .coa-children.collapsed {
            display: none;
        }
        .coa-node.hidden-by-search {
            display: none;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('assets/system/js/pages/chart-of-accounts.js') }}"></script>
@endpush
