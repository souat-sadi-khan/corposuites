@extends('admin.layout.app', ['title' => 'POS Sales'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="posSaleSearch" placeholder="Search Sales">
        </div>

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

        <select id="posStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Sale Status</option>
            <option value="completed">Completed</option>
            <option value="voided">Voided</option>
        </select>

        <div class="tl-spacer"></div>

        <!-- Open Terminal -->
        <a href="{{ route('admin.pos.terminal') }}" class="btn-nx-primary">
            <i class="ri-store-2-line"></i>
            Open Terminal
        </a>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="posSaleTable" data-url="{{ route('admin.pos.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sale #</th>
                        <th>Items</th>
                        <th>Sold At</th>
                        <th>Payment Method</th>
                        <th>Grand Total</th>
                        <th>Sale Status</th>
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
    <script src="{{ asset('assets/system/js/pages/pos-sales.js') }}"></script>
@endpush
