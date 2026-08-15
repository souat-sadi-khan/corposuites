@extends('admin.layout.app', ['title' => 'Email Templates', 'modal' => 'lg', 'offcanvas' => '70%'])

@section('content')
    <!-- Toolbar -->
    <div class="tl-toolbar">
        <!-- Search -->
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="templateSearch" placeholder="Search templates...">
        </div>

        <!-- Filters -->
        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="tlFilterBtn" title="Filter">
                <i class="ri-equalizer-line"></i>
            </button>

            <div class="tl-filter-dd" id="tlFilterDd">
                <div class="tl-filter-dd-title">
                    Filter by Status
                </div>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="1" checked> Active
                </label>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="0" checked> Inactive
                </label>

                <div class="tl-filter-dd-title mt-2">
                    Category
                </div>
                <select id="categoryFilter" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <option value="welcome">Welcome</option>
                    <option value="notification">Notification</option>
                    <option value="newsletter">Newsletter</option>
                    <option value="password">Password</option>
                    <option value="verification">Verification</option>
                    <option value="onboarding">Onboarding</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>

        <!-- Selected -->
        <label class="tl-selected-chip" id="tlSelectedChip" style="display:none">
            <input type="checkbox" checked disabled>
            <span id="tlSelectedCount">0 Selected</span>
        </label>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.email.email-templates.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Template
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="templateTable" data-url="{{ route('admin.email.email-templates.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th class="no-sort tl-check-col">
                            <input type="checkbox" id="selectAllChk">
                        </th>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Status</th>
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
    <script src="{{ asset('assets/system/js/pages/email-templates.js') }}"></script>
@endpush
