@extends('admin.layout.app', ['title' => 'Debit Notes', 'modal' => 'xl'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="debitNoteSearch" placeholder="Search Debit Notes">
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

        <select id="debitStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Debit Status</option>
            <option value="draft">Draft</option>
            <option value="issued">Issued</option>
            <option value="applied">Applied</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.debit-notes.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Debit Note
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="debitNoteTable" data-url="{{ route('admin.debit-notes.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Debit Note #</th>
                        <th>Invoice</th>
                        <th>Items</th>
                        <th>Debit Date</th>
                        <th>Grand Total</th>
                        <th>Debit Status</th>
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
    <script src="{{ asset('assets/system/js/pages/debit-notes.js') }}"></script>
@endpush
