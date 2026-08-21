@extends('admin.layout.app', ['title' => 'Employees', 'offcanvas' => '80%', 'modal' => 'xl'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="employeeSearch" placeholder="Search Employees">
        </div>

        <!-- Advanced Search -->
        <button type="button" class="btn-nx-outline adv-search-btn" data-bs-toggle="modal" data-bs-target="#employeeAdvSearchModal" title="Advanced Search">
            <i class="ri-filter-3-line"></i>
            Advanced Search
            <span class="adv-search-badge" id="advSearchBadge" style="display:none;">0</span>
        </button>

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

    <!-- Active Advanced Search Filters -->
    <div class="adv-search-chips" id="advSearchChipsBar" style="display:none;">
        <span class="adv-search-chips-label"><i class="ri-price-tag-3-line"></i> Filters:</span>
        <div id="advSearchChips" class="d-flex align-items-center gap-2 flex-wrap"></div>
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

    <!-- Advanced Search Modal -->
    <div class="modal fade" id="employeeAdvSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="nx-modal-box fm-modal-content">
                <div class="modal-header fm-modal-head">
                    <div>
                        <h5 class="modal-title"><i class="ri-filter-3-line me-1"></i> Advanced Search</h5>
                        <p>Combine any of the fields below to narrow down employees.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body fm-modal-body fm-body">
                    <div class="fm-grid">

                        <div class="adv-search-section"><i class="ri-briefcase-4-line"></i> Employment</div>

                        <div class="fm-field">
                            <label>Employee Type</label>
                            <select id="advEmployeeType" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All Types</option>
                                @foreach($employeeTypes as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Employment Status</label>
                            <select id="advEmploymentStatus" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All Statuses</option>
                                @foreach($employmentStatuses as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Shift</label>
                            <select id="advShift" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All Shifts</option>
                                @foreach($shifts as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Record Status</label>
                            <select id="advRecordStatus" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">Active and Inactive</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="adv-search-section"><i class="ri-organization-chart"></i> Organization</div>

                        <div class="fm-field">
                            <label>Department</label>
                            <select id="advDepartment" class="form-select" data-placeholder="All Departments">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Designation</label>
                            <select id="advDesignation" class="form-select" data-placeholder="All Designations">
                                <option value="">All Designations</option>
                                @foreach($designations as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->name }}{{ $designation->department ? ' — '.$designation->department->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Gender</label>
                            <select id="advGender" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All Genders</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="adv-search-section"><i class="ri-calendar-2-line"></i> Joining Date Range</div>

                        <div class="fm-field">
                            <label>From</label>
                            <input type="date" id="advJoiningFrom" class="form-control">
                        </div>

                        <div class="fm-field">
                            <label>To</label>
                            <input type="date" id="advJoiningTo" class="form-control">
                        </div>

                        <div class="adv-search-section"><i class="ri-cake-2-line"></i> Birth Date Range</div>

                        <div class="fm-field">
                            <label>From</label>
                            <input type="date" id="advBirthFrom" class="form-control">
                        </div>

                        <div class="fm-field">
                            <label>To</label>
                            <input type="date" id="advBirthTo" class="form-control">
                        </div>

                    </div>
                </div>

                <div class="modal-footer fm-modal-foot">
                    <span class="fm-foot-note">
                        <i class="ri-information-line"></i> Leave a field empty to skip that filter
                    </span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-nx-outline" id="advSearchReset">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </button>
                        <button type="button" class="btn-nx-primary" id="advSearchApply">
                            <i class="ri-search-line me-1"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/employees.js') }}"></script>
@endpush
