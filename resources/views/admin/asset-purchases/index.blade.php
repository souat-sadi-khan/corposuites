@extends('admin.layout.app', ['title' => 'Asset Purchase Information', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="assetPurchaseSearch" placeholder="Search by asset or invoice">
        </div>

        <select id="vendorFilter" class="form-select form-select-sm w-auto">
            <option value="">All Vendors</option>
            @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
            @endforeach
        </select>

        <select id="warrantyFilter" class="form-select form-select-sm w-auto">
            <option value="">All Warranty States</option>
            <option value="active">Under Warranty</option>
            <option value="expired">Warranty Expired</option>
            <option value="none">No Warranty</option>
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

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.asset-purchases.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Record Purchase
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="assetPurchaseTable" data-url="{{ route('admin.asset-purchases.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asset</th>
                        <th>Vendor</th>
                        <th>PO / Invoice</th>
                        <th>Purchase Date</th>
                        <th>Total Cost</th>
                        <th>Warranty</th>
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
    <script src="{{ asset('assets/system/js/pages/asset-purchases.js') }}"></script>
@endpush
