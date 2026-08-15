@extends('admin.layout.app', ['title' => 'Goods Receipts', 'modal' => 'xl'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="goodsReceiptSearch" placeholder="Search Receipts">
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

        <select id="purchaseOrderFilter" class="form-select form-select-sm w-auto">
            <option value="">All Orders</option>
            @foreach($purchaseOrders as $purchaseOrder)
                <option value="{{ $purchaseOrder->id }}">{{ $purchaseOrder->po_number }}</option>
            @endforeach
        </select>

        <select id="receiptStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Receipt Status</option>
            <option value="pending">Pending</option>
            <option value="received">Received</option>
            <option value="inspected">Inspected</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.goods-receipts.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Receipt
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="goodsReceiptTable" data-url="{{ route('admin.goods-receipts.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Receipt #</th>
                        <th>Items</th>
                        <th>Received Date</th>
                        <th>Receipt Status</th>
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
    <script src="{{ asset('assets/system/js/pages/goods-receipts.js') }}"></script>
@endpush
