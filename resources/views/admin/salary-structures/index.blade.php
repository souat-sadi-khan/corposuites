@extends('admin.layout.app', ['title' => 'Salary Structures', 'modal' => 'lg'])

@section('content')
    <style>
        .sel-opt-rich-info {
            flex: 1;
            min-width: 0;
        }

        .sel-opt-rich-name-row {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .sel-opt-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.4;
        }

        .sel-opt-type-badge.bg-success-subtle {
            background: rgba(25, 135, 84, 0.10);
        }

        .sel-opt-type-badge.bg-danger-subtle {
            background: rgba(220, 53, 69, 0.10);
        }

        .sel-opt-percentage {
            font-size: 11px;
            font-weight: 700;
            color: #6c757d;
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .sel-opt-rich-desc {
            margin-top: 2px;
            font-size: 11px;
            color: #8a9199;
            line-height: 1.4;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .salary-component-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 180px 42px;
            gap: 10px;
            align-items: center;
        }

        .salary-component-row .fm-field {
            margin: 0;
        }

        .salary-component-action {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .salary-component-action .btn {
            width: 38px;
            height: 38px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .salary-summary {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .salary-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 12px;
            border-bottom: 1px solid #eef0f2;
            font-size: 13px;
        }

        .salary-summary-row:last-child {
            border-bottom: 0;
        }

        .salary-summary-row span {
            color: #667085;
        }

        .salary-summary-row strong {
            font-size: 13px;
            font-weight: 600;
        }

        .salary-summary-row small {
            font-size: 11px;
            font-weight: 600;
        }

        .salary-gross-row {
            background: #f8f9fa;
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .salary-gross-row span {
            color: #1f2937;
            font-weight: 700;
        }

        .salary-gross-row strong {
            font-size: 15px;
            color: #1f2937;
        }
    </style>
    @if(request('employee_id'))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span><i class="ri-filter-3-line me-1"></i> Showing salary structures for the selected employee.</span>
            <a href="{{ route('admin.salary-structures.index') }}" class="btn-nx-outline btn-sm">Clear Filter</a>
        </div>
    @endif

    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="salaryStructureSearch" placeholder="Search Salary Structures">
        </div>

        <!-- Advanced Search -->
        <button type="button" class="btn-nx-outline adv-search-btn" data-bs-toggle="modal" data-bs-target="#salaryAdvSearchModal" title="Advanced Search">
            <i class="ri-filter-3-line"></i>
            Advanced Search
            <span class="adv-search-badge" id="advSearchBadge" style="display:none;">0</span>
        </button>

        <div class="tl-spacer"></div>

        <!-- How To -->
        <button id="openModal" data-url="{{ route('admin.salary-structures.how.to') }}" class="btn-nx-outline">
            <i class="ri-question-mark"></i>
        </button>

        <!-- Add Button -->
        @can('salary-structure.create')
        <button id="openModal" data-url="{{ route('admin.salary-structures.create', request('employee_id') ? ['employee_id' => request('employee_id')] : []) }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Salary Structure
        </button>
        @endcan
    </div>

    <!-- Active Advanced Search Filters -->
    <div class="adv-search-chips" id="advSearchChipsBar" style="display:none;">
        <span class="adv-search-chips-label"><i class="ri-price-tag-3-line"></i> Filters:</span>
        <div id="advSearchChips" class="d-flex align-items-center gap-2 flex-wrap"></div>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="salaryStructureTable" data-url="{{ route('admin.salary-structures.index') }}" data-employee-id="{{ request('employee_id') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Pay Type</th>
                        <th>Effective Date</th>
                        <th>Salary</th>
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
    <div class="modal fade" id="salaryAdvSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="nx-modal-box fm-modal-content">
                <div class="modal-header fm-modal-head">
                    <div>
                        <h5 class="modal-title"><i class="ri-filter-3-line me-1"></i> Advanced Search</h5>
                        <p>Combine any of the fields below to narrow down salary structures.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body fm-modal-body fm-body">
                    <div class="fm-grid">

                        <div class="adv-search-section"><i class="ri-user-3-line"></i> Employee &amp; Organization</div>

                        <div class="fm-field fm-full">
                            <label>Employee</label>
                            <select id="advEmployee" class="form-select" data-placeholder="All Employees">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                    <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}" value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Department</label>
                            <select id="advDepartment" class="form-select" data-placeholder="All Departments">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option data-desc="{{ $department->description }}" value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Designation</label>
                            <select id="advDesignation" class="form-select" data-placeholder="All Designations">
                                <option value="">All Designations</option>
                                @foreach($designations as $designation)
                                    <option data-desc="{{ $designation->description }}" value="{{ $designation->id }}">{{ $designation->name }}{{ $designation->department ? ' — '.$designation->department->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="adv-search-section"><i class="ri-wallet-3-line"></i> Pay Details</div>

                        <div class="fm-field">
                            <label>Pay Type</label>
                            <select id="advPayType" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All Pay Types</option>
                                <option value="monthly">Monthly</option>
                                <option value="daily">Daily</option>
                                <option value="commission">Commission-based</option>
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Status</label>
                            <select id="advStatus" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="adv-search-section"><i class="ri-calendar-2-line"></i> Effective Date Range</div>

                        <div class="fm-field">
                            <label>From</label>
                            <input type="date" id="advEffFrom" class="form-control">
                        </div>

                        <div class="fm-field">
                            <label>To</label>
                            <input type="date" id="advEffTo" class="form-control">
                        </div>

                        <div class="adv-search-section"><i class="ri-money-dollar-circle-line"></i> Salary / Rate Range</div>

                        <div class="fm-field">
                            <label>Minimum</label>
                            <input type="number" step="0.01" min="0" id="advSalaryMin" class="form-control" placeholder="e.g. 500">
                        </div>

                        <div class="fm-field">
                            <label>Maximum</label>
                            <input type="number" step="0.01" min="0" id="advSalaryMax" class="form-control" placeholder="e.g. 5000">
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
    <script src="{{ asset('assets/system/js/pages/salary-structures.js') }}"></script>
@endpush
