@extends('admin.layout.app', ['title' => 'Employees', 'offcanvas' => '80%', 'modal' => 'xl'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="employeeSearch" placeholder="Search Employees">
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

        <a href="{{ route('admin.employees.export') }}" class="btn-nx-outline">
            <i class="ri-download-2-line"></i> Export
        </a>

        <button class="btn-nx-outline side-offcanvas" data-url="{{ route('admin.employees.import-form') }}" data-width="450px">
            <i class="ri-upload-2-line"></i> Import
        </button>

        <!-- Add Button -->
        <button class="btn-nx-primary side-offcanvas" data-url="{{ route('admin.employees.create') }}">
            <i class="ri-add-line"></i>
            Add Employee
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="employeeTable" data-url="{{ route('admin.employees.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Contact</th>
                        <th>Type / Status</th>
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
    <script src="{{ asset('assets/system/js/pages/employees.js') }}"></script>
@endpush
