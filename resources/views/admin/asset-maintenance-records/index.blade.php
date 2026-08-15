@extends('admin.layout.app', ['title' => 'Maintenance History', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="recordSearch" placeholder="Search by title or asset">
        </div>

        <select id="recordAssetFilter" class="form-select form-select-sm w-auto">
            <option value="">All Assets</option>
            @foreach($assets as $asset)
                <option value="{{ $asset->id }}">{{ $asset->asset_code }} - {{ $asset->name }}</option>
            @endforeach
        </select>

        <select id="originFilter" class="form-select form-select-sm w-auto">
            <option value="">Planned &amp; Unplanned</option>
            <option value="planned">Planned Only</option>
            <option value="unplanned">Unplanned Only</option>
        </select>

        <select id="recordStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Work States</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
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

        <a href="{{ route('admin.asset-maintenance-schedules.index') }}" class="btn-nx-outline">
            <i class="ri-tools-line"></i> Schedules
        </a>

        <button id="openModal" data-url="{{ route('admin.asset-maintenance-records.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Log Maintenance
        </button>
    </div>

    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="recordTable" data-url="{{ route('admin.asset-maintenance-records.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Work</th>
                        <th>Origin</th>
                        <th>Type</th>
                        <th>Performed</th>
                        <th>By</th>
                        <th>Cost</th>
                        <th>State</th>
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
    <script src="{{ asset('assets/system/js/pages/asset-maintenance-records.js') }}"></script>
@endpush
