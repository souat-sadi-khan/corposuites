@extends('admin.layout.app', ['title' => 'Disposal Management', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="disposalSearch" placeholder="Search by asset or recipient">
        </div>

        <select id="disposalMethodFilter" class="form-select form-select-sm w-auto">
            <option value="">All Methods</option>
            <option value="sold">Sold</option>
            <option value="scrapped">Scrapped</option>
            <option value="donated">Donated</option>
            <option value="written_off">Written Off</option>
            <option value="traded_in">Traded In</option>
            <option value="lost">Lost</option>
        </select>

        <select id="outcomeFilter" class="form-select form-select-sm w-auto">
            <option value="">Gain &amp; Loss</option>
            <option value="gain">Gain Only</option>
            <option value="loss">Loss Only</option>
        </select>

        <select id="disposalStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Disposal States</option>
            <option value="pending">Pending</option>
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

        <button id="openModal" data-url="{{ route('admin.asset-disposals.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Record Disposal
        </button>
    </div>

    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="disposalTable" data-url="{{ route('admin.asset-disposals.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asset</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Proceeds</th>
                        <th>Gain / Loss</th>
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
    <script src="{{ asset('assets/system/js/pages/asset-disposals.js') }}"></script>
@endpush
