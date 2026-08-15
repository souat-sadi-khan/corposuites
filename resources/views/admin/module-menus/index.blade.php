@extends('admin.layout.app', ['title' => 'Module Menus', 'modal' => 'lg', 'offcanvas' => '70%'])

@section('content')
    <!-- Toolbar -->
    <div class="tl-toolbar">
        <!-- Search -->
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="menuSearch" placeholder="Search Menus">
        </div>

        <!-- Filter: Module -->
        <div class="tl-filter-wrap fm-body" style="margin-left: 10px;">
            <select id="moduleFilter" class="form-select select" style="min-width: 250px;">
                <option value="">All Modules</option>
                @foreach($modules as $mod)
                    <option value="{{ $mod->id }}" {{ $moduleId == $mod->id ? 'selected' : '' }}>{{ $mod->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Filter: Status -->
        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="tlFilterBtn" title="Filter">
                <i class="ri-equalizer-line"></i>
            </button>
            <div class="tl-filter-dd" id="tlFilterDd">
                <div class="tl-filter-dd-title">Filter by Status</div>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="1" checked> Active
                </label>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="0" checked> Inactive
                </label>
            </div>
        </div>

        <div class="tl-spacer"></div>

        <!-- Add Button (opens offcanvas) -->
        <button data-url="{{ route('admin.module-menus.create') }}" class="side-offcanvas btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Menu
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="menuTable" data-url="{{ route('admin.module-menus.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Module</th>
                        <th>Label</th>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Permission</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th class="no-sort text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="tl-footer">
            <div class="tl-info" id="tlInfo"></div>
            <div class="tl-pagination">
                <button class="tl-page-btn" id="tlPrev" title="Previous page">
                    <i class="ri-arrow-left-s-line"></i>
                </button>
                <button class="tl-page-btn" id="tlNext" title="Next page">
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/module-menus.js') }}"></script>
@endpush
