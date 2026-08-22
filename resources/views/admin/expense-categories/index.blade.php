@extends('admin.layout.app', ['title' => 'Expense Categories', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="expenseCategorySearch" placeholder="Search Expense Categories">
        </div>

        <!-- Advanced Search -->
        <button type="button" class="btn-nx-outline adv-search-btn" data-bs-toggle="modal" data-bs-target="#expenseCategoryAdvSearchModal" title="Advanced Search">
            <i class="ri-filter-3-line"></i>
            Advanced Search
            <span class="adv-search-badge" id="advSearchBadge" style="display:none;">0</span>
        </button>

        <div class="tl-spacer"></div>

        <!-- How To -->
        <button id="openModal" data-url="{{ route('admin.expense-categories.how.to') }}" class="btn-nx-outline">
            <i class="ri-question-mark"></i>
        </button>

        <button id="openModal" data-url="{{ route('admin.expense-categories.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Expense Category
        </button>
    </div>

    <!-- Active Advanced Search Filters -->
    <div class="adv-search-chips" id="advSearchChipsBar" style="display:none;">
        <span class="adv-search-chips-label"><i class="ri-price-tag-3-line"></i> Filters:</span>
        <div id="advSearchChips" class="d-flex align-items-center gap-2 flex-wrap"></div>
    </div>

    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="expenseCategoryTable" data-url="{{ route('admin.expense-categories.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Spending Policy</th>
                        <th>GL Account</th>
                        <th>Claims</th>
                        <th>Status</th>
                        <th class="no-sort text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

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
    <div class="modal fade" id="expenseCategoryAdvSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="nx-modal-box fm-modal-content">
                <div class="modal-header fm-modal-head">
                    <div>
                        <h5 class="modal-title"><i class="ri-filter-3-line me-1"></i> Advanced Search</h5>
                        <p>Combine any of the fields below to narrow down expense categories.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body fm-modal-body fm-body">
                    <div class="fm-grid">

                        <div class="adv-search-section"><i class="ri-shield-check-line"></i> Spending Policy</div>

                        <div class="fm-field">
                            <label>Has Spending Cap</label>
                            <select id="advHasLimit" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">Either</option>
                                <option value="1">Yes — cap configured</option>
                                <option value="0">No — uncapped</option>
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Requires Receipt Above a Threshold</label>
                            <select id="advReceiptRequired" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">Either</option>
                                <option value="1">Yes — threshold configured</option>
                                <option value="0">No — always optional</option>
                            </select>
                        </div>

                        <div class="fm-field">
                            <label>Max Amount Min</label>
                            <input type="number" step="0.01" min="0" id="advMaxAmountMin" class="form-control" placeholder="e.g. 100">
                        </div>

                        <div class="fm-field">
                            <label>Max Amount Max</label>
                            <input type="number" step="0.01" min="0" id="advMaxAmountMax" class="form-control" placeholder="e.g. 1000">
                        </div>

                        <div class="adv-search-section"><i class="ri-bank-line"></i> GL Mapping &amp; Status</div>

                        <div class="fm-field fm-full">
                            <label>GL Account</label>
                            <select id="advChartOfAccount" class="form-select" data-placeholder="Any GL Account">
                                <option value="">Any</option>
                                <option value="none">Not mapped</option>
                                @foreach($chartOfAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
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
    <script src="{{ asset('assets/system/js/pages/expense-categories.js') }}"></script>
@endpush
