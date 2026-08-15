@extends('admin.layout.app', ['title' => 'Purchase Invoice Matching', 'modal' => 'xl'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="purchaseInvoiceSearch" placeholder="Search Invoices">
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

        <select id="vendorFilter" class="form-select form-select-sm w-auto">
            <option value="">All Vendors</option>
            @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
            @endforeach
        </select>

        <select id="matchStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Match Status</option>
            <option value="unmatched">Unmatched</option>
            <option value="matched">Matched</option>
            <option value="discrepancy">Discrepancy</option>
        </select>

        <select id="invoiceStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Invoice Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="paid">Paid</option>
            <option value="disputed">Disputed</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.purchase-invoices.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Invoice
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="purchaseInvoiceTable" data-url="{{ route('admin.purchase-invoices.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice #</th>
                        <th>PO #</th>
                        <th>Items</th>
                        <th>Invoice Date</th>
                        <th>Grand Total</th>
                        <th>Balance Due</th>
                        <th>Match Status</th>
                        <th>Invoice Status</th>
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
    <script src="{{ asset('assets/system/js/pages/purchase-invoices.js') }}"></script>
@endpush
