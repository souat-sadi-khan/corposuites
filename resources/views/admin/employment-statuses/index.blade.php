@extends('admin.layout.app', ['title' => 'Employment Statuses', 'modal' => 'lg', 'offcanvas' => '680px'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="employmentStatusSearch" placeholder="Search Employment Statuses">
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

        <div class="tl-spacer"></div>

        <!-- How To -->
        <button id="openModal" data-url="{{ route('admin.employment-statuses.how.to') }}" class="btn-nx-outline">
            <i class="ri-question-mark"></i>
        </button>

        <!-- Add Button -->
        @if(Auth::guard('admin')->user()?->can('employment-status.create'))
        <button id="openModal" data-url="{{ route('admin.employment-statuses.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Employment Status
        </button>
        @endif
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="employmentStatusTable" data-url="{{ route('admin.employment-statuses.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Employee Count</th>
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
    <script src="{{ asset('assets/system/js/pages/employment-statuses.js') }}"></script>
@endpush
