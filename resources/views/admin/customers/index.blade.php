@extends('admin.layout.app', ['title' => 'Customers', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="customerSearch" placeholder="Search Customers">
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

        <select id="customerGroupFilter" class="form-select form-select-sm w-auto">
            <option value="">All Customer Groups</option>
            @foreach($customerGroups as $group)
                <option value="{{ $group->id }}">{{ $group->name }}</option>
            @endforeach
        </select>

        <select id="paymentTermFilter" class="form-select form-select-sm w-auto">
            <option value="">All Payment Terms</option>
            @foreach($paymentTerms as $term)
                <option value="{{ $term->id }}">{{ $term->name }}</option>
            @endforeach
        </select>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.customers.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Customer
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="customerTable" data-url="{{ route('admin.customers.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Company</th>
                        <th>Customer Group</th>
                        <th>Payment Term</th>
                        <th>Credit Limit</th>
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
    <script src="{{ asset('assets/system/js/pages/customers.js') }}"></script>
@endpush
