@extends('admin.layout.app', ['title' => 'Payroll', 'modal' => 'lg'])

@section('content')
    @if(request('employee_id'))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span><i class="ri-filter-3-line me-1"></i> Showing payroll records for the selected employee.</span>
            <a href="{{ route('admin.payrolls.index') }}" class="btn-nx-outline btn-sm">Clear Filter</a>
        </div>
    @endif

    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="payrollSearch" placeholder="Search Payroll">
        </div>

        <!-- Advanced Search -->
        <button type="button" class="btn-nx-outline adv-search-btn" data-bs-toggle="modal" data-bs-target="#payrollAdvSearchModal" title="Advanced Search">
            <i class="ri-filter-3-line"></i>
            Advanced Search
            <span class="adv-search-badge" id="advSearchBadge" style="display:none;">0</span>
        </button>

        <div class="tl-spacer"></div>

        <!-- How To -->
        <button id="openModal" data-url="{{ route('admin.payrolls.how.to') }}" class="btn-nx-outline">
            <i class="ri-question-mark"></i>
        </button>

        <!-- Generate for All -->
        @if(Auth::guard('admin')->user()?->can('payroll.bulk-generate'))
        <button id="openModal" data-url="{{ route('admin.payrolls.bulk-generate-form') }}" class="btn-nx-outline">
            <i class="ri-team-line"></i>
            Generate for All
        </button>
        @endif

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.payrolls.create', request('employee_id') ? ['employee_id' => request('employee_id')] : []) }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Generate Payroll
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
            <table id="payrollTable" data-url="{{ route('admin.payrolls.index') }}" data-employee-id="{{ request('employee_id') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Salary</th>
                        <th>Payment</th>
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
    <div class="modal fade" id="payrollAdvSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="nx-modal-box fm-modal-content">
                <div class="modal-header fm-modal-head">
                    <div>
                        <h5 class="modal-title"><i class="ri-filter-3-line me-1"></i> Advanced Search</h5>
                        <p>Combine any of the fields below to narrow down payroll records.</p>
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
                            <label>Reimbursement Status</label>
                            <select id="advPaymentStatus" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
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

                        <div class="adv-search-section"><i class="ri-calendar-2-line"></i> Pay Period</div>

                        <div class="fm-field">
                            <label>Month</label>
                            <select id="advMonth" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">Any Month</option>
                                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $monthName)
                                    <option value="{{ $i + 1 }}">{{ $monthName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Year</label>
                            <select id="advYear" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">Any Year</option>
                                @for($y = now()->year + 1; $y >= now()->year - 6; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="adv-search-section"><i class="ri-money-dollar-circle-line"></i> Net Salary Range</div>

                        <div class="fm-field">
                            <label>Minimum</label>
                            <input type="number" step="0.01" min="0" id="advNetSalaryMin" class="form-control" placeholder="e.g. 500">
                        </div>

                        <div class="fm-field">
                            <label>Maximum</label>
                            <input type="number" step="0.01" min="0" id="advNetSalaryMax" class="form-control" placeholder="e.g. 5000">
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
    <script src="{{ asset('assets/system/js/pages/payrolls.js') }}"></script>
@endpush
