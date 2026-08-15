@extends('admin.layout.app', ['title' => 'Asset Location Tracking', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="movementSearch" placeholder="Search by asset or location">
        </div>

        <select id="movementLocationFilter" class="form-select form-select-sm w-auto">
            <option value="">All Locations</option>
            @foreach($assetLocations as $location)
                <option value="{{ $location->id }}">{{ $location->name }} ({{ $location->code }})</option>
            @endforeach
        </select>

        <select id="currentOnlyFilter" class="form-select form-select-sm w-auto">
            <option value="">Full History</option>
            <option value="1">Current Location Only</option>
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

        <a href="{{ route('admin.asset-locations.index') }}" class="btn-nx-outline">
            <i class="ri-map-pin-line"></i> Manage Locations
        </a>

        <button id="openModal" data-url="{{ route('admin.asset-location-movements.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Record Movement
        </button>
    </div>

    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="movementTable" data-url="{{ route('admin.asset-location-movements.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asset</th>
                        <th>Location</th>
                        <th>Moved On</th>
                        <th>Current</th>
                        <th>Moved By</th>
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
    <script src="{{ asset('assets/system/js/pages/asset-location-movements.js') }}"></script>
@endpush
