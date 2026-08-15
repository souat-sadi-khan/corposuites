@extends('admin.layout.app', ['title' => 'Sender Identities', 'modal' => 'lg', 'offcanvas' => '70%'])

@section('content')
    <!-- Toolbar -->
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="senderSearch" placeholder="Search Senders">
        </div>

        <!-- Filter -->
        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="tlFilterBtn" title="Filter">
                <i class="ri-equalizer-line"></i>
            </button>
            <div class="tl-filter-dd" id="tlFilterDd">
                <div class="tl-filter-dd-title">Filter by Default</div>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="1" checked> Default
                </label>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="0" checked> Non-Default
                </label>
            </div>
        </div>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.email.sender-identities.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i> Add Sender
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="senderTable" data-url="{{ route('admin.email.sender-identities.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th class="tl-check-col">
                            <input type="checkbox" id="selectAllChk">
                        </th>
                        <th>Provider</th>
                        <th>Sender</th>
                        <th>Default</th>
                        <th class="no-sort text-end">
                            <i class="ri-more-2-fill"></i>
                        </th>
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
    <script src="{{ asset('assets/system/js/pages/sender-identities.js') }}"></script>
@endpush
