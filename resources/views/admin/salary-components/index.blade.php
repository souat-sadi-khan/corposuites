@extends('admin.layout.app', ['title' => 'Salary Components', 'modal' => 'lg', 'offcanvas' => '800px'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="salaryComponentSearch" placeholder="Search Salary Components">
        </div>

        <!-- Advanced Search -->
        <button type="button" class="btn-nx-outline adv-search-btn" data-bs-toggle="modal" data-bs-target="#componentAdvSearchModal" title="Advanced Search">
            <i class="ri-filter-3-line"></i>
            Advanced Search
            <span class="adv-search-badge" id="advSearchBadge" style="display:none;">0</span>
        </button>

        <div class="tl-spacer"></div>

        <!-- How To -->
        <button id="openModal" data-url="{{ route('admin.salary-components.how.to') }}" class="btn-nx-outline">
            <i class="ri-question-mark"></i>
        </button>

        <!-- Add Button -->
        @if(Auth::guard('admin')->user()->can('salary-component.create'))
            <button id="openModal" data-url="{{ route('admin.salary-components.create') }}" class="btn-nx-primary">
                <i class="ri-add-line"></i>
                Add Salary Component
            </button>
        @endif
    </div>

    <!-- Active Advanced Search Filters -->
    <div class="adv-search-chips" id="advSearchChipsBar" style="display:none;">
        <span class="adv-search-chips-label"><i class="ri-price-tag-3-line"></i> Filters:</span>
        <div id="advSearchChips" class="d-flex align-items-center gap-2 flex-wrap"></div>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="salaryComponentTable" data-url="{{ route('admin.salary-components.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Value</th>
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
    <div class="modal fade" id="componentAdvSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="nx-modal-box fm-modal-content">
                <div class="modal-header fm-modal-head">
                    <div>
                        <h5 class="modal-title"><i class="ri-filter-3-line me-1"></i> Advanced Search</h5>
                        <p>Combine any of the fields below to narrow down salary components.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body fm-modal-body fm-body">
                    <div class="fm-grid">

                        <div class="adv-search-section"><i class="ri-swap-line"></i> Type &amp; Calculation</div>

                        <div class="fm-field">
                            <label>Type</label>
                            <select id="advType" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All Types</option>
                                <option value="earning">Earning</option>
                                <option value="deduction">Deduction</option>
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Calculation Type</label>
                            <select id="advCalculationType" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All Calculation Types</option>
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                                <option value="per_occurrence">Per Occurrence</option>
                            </select>
                        </div>

                        <div class="adv-search-section"><i class="ri-money-dollar-circle-line"></i> Value Range</div>

                        <div class="fm-field">
                            <label>Minimum</label>
                            <input type="number" step="0.01" min="0" id="advValueMin" class="form-control" placeholder="e.g. 0">
                        </div>

                        <div class="fm-field">
                            <label>Maximum</label>
                            <input type="number" step="0.01" min="0" id="advValueMax" class="form-control" placeholder="e.g. 100">
                        </div>

                        <div class="adv-search-section"><i class="ri-file-list-3-line"></i> Other</div>

                        <div class="fm-field">
                            <label>Taxable</label>
                            <select id="advTaxable" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">All</option>
                                <option value="1">Taxable</option>
                                <option value="0">Non-taxable</option>
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
    <script src="{{ asset('assets/system/js/pages/salary-components.js') }}"></script>
@endpush
