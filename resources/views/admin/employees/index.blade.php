@extends('admin.layout.app', ['title' => 'Employees', 'offcanvas' => '80%', 'modal' => 'xl'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="employeeSearch" placeholder="Search Employees">
        </div>

        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="employeeAdvancedFilterBtn" title="Advanced filters">
                <i class="ri-equalizer-line"></i>
            </button>
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

    <div id="employeeAdvancedFilters" class="nx-card p-3 mb-3" style="display:none">
        <div class="d-flex justify-content-between align-items-center mb-3"><h6 class="mb-0">Advanced employee filters</h6><button type="button" id="clearEmployeeFilters" class="btn btn-sm btn-outline-secondary">Clear all</button></div>
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Employment Type</label><select class="form-select employee-filter" name="employee_type_id"><option value="">All types</option>@foreach($employeeTypes as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Employment Status</label><select class="form-select employee-filter" name="employment_status_id"><option value="">All statuses</option>@foreach($employmentStatuses as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Record Status</label><select class="form-select employee-filter" name="status"><option value="">Active and inactive</option><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="col-md-3"><label class="form-label">Shift</label><select class="form-select employee-filter" name="shift_id"><option value="">All shifts</option>@foreach($shifts as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Department</label><select class="form-select employee-filter" name="department_id"><option value="">All departments</option>@foreach($departments as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Designation</label><select class="form-select employee-filter" name="designation_id"><option value="">All designations</option>@foreach($designations as $item)<option value="{{ $item->id }}">{{ $item->name }}{{ $item->department ? ' — '.$item->department->name : '' }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Gender</label><select class="form-select employee-filter" name="gender"><option value="">All genders</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
            <div class="col-md-3"><label class="form-label">Joining date from</label><input class="form-control employee-filter" name="joining_from" type="date"></div>
            <div class="col-md-3"><label class="form-label">Joining date to</label><input class="form-control employee-filter" name="joining_to" type="date"></div>
            <div class="col-md-3"><label class="form-label">Birth date from</label><input class="form-control employee-filter" name="birth_from" type="date"></div>
            <div class="col-md-3"><label class="form-label">Birth date to</label><input class="form-control employee-filter" name="birth_to" type="date"></div>
        </div>
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
