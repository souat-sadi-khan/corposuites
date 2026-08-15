@extends('admin.layout.app', ['title' => 'Asset Locations', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="assetLocationSearch" placeholder="Search Locations">
        </div>

        <select id="locationTypeFilter" class="form-select form-select-sm w-auto">
            <option value="">All Types</option>
            <option value="office">Office</option>
            <option value="branch">Branch</option>
            <option value="warehouse">Warehouse</option>
            <option value="site">Site</option>
            <option value="other">Other</option>
        </select>

        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="tlFilterBtn" title="Filter">
                <i class="ri-equalizer-line"></i>
            </button>

            <div class="tl-filter-dd" id="tlFilterDd">
                <div class="tl-filter-dd-title">
                    Filter by Status
                </div>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="1" checked>
                    Active
                </label>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="0" checked>
                    Inactive
                </label>
            </div>
        </div>

        <div class="tl-spacer"></div>

        <a href="{{ route('admin.asset-location-movements.index') }}" class="btn-nx-outline">
            <i class="ri-route-line"></i> Movement Tracking
        </a>

        <button id="openModal" data-url="{{ route('admin.asset-locations.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Location
        </button>
    </div>

    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="assetLocationTable" data-url="{{ route('admin.asset-locations.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Placement</th>
                        <th>Department</th>
                        <th>Movements</th>
                        <th>Status</th>
                        <th class="no-sort text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

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
    <script src="{{ asset('assets/system/js/pages/asset-locations.js') }}"></script>
@endpush
