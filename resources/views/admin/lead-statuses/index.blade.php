@extends('admin.layout.app', ['title' => 'Lead Statuses', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="leadStatusSearch" placeholder="Search Lead Statuses">
        </div>

        <button type="button" class="btn-nx-outline adv-search-btn" data-bs-toggle="modal" data-bs-target="#leadStatusAdvSearchModal" title="Advanced Search">
            <i class="ri-filter-3-line"></i>
            Advanced Search
            <span class="adv-search-badge" id="advSearchBadge" style="display:none;">0</span>
        </button>

        <div class="tl-spacer"></div>

        <button id="openModal" data-url="{{ route('admin.lead-statuses.how.to') }}" class="btn-nx-outline" title="How To">
            <i class="ri-question-mark"></i>
        </button>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.lead-statuses.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Lead Status
        </button>
    </div>

    <div class="adv-search-chips" id="advSearchChipsBar" style="display:none;">
        <span class="adv-search-chips-label"><i class="ri-price-tag-3-line"></i> Filters:</span>
        <div id="advSearchChips" class="d-flex align-items-center gap-2 flex-wrap"></div>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="leadStatusTable" data-url="{{ route('admin.lead-statuses.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
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

    <div class="modal fade" id="leadStatusAdvSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="nx-modal-box fm-modal-content">
                <div class="modal-header fm-modal-head">
                    <div>
                        <h5 class="modal-title"><i class="ri-filter-3-line me-1"></i> Advanced Search</h5>
                        <p>Combine filters to find the lead statuses you need.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body fm-modal-body fm-body">
                    <div class="fm-grid">
                        <div class="adv-search-section"><i class="ri-flag-2-line"></i> Lead Status Details</div>
                        <div class="fm-field">
                            <label>Record Status</label>
                            <select id="advStatus" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">Active and Inactive</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="fm-field">
                            <label>Description</label>
                            <select id="advHasDescription" class="form-select as-select" data-minimum-results-for-search="Infinity">
                                <option value="">Any</option>
                                <option value="1">Has a description</option>
                                <option value="0">No description</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer fm-modal-foot">
                    <span class="fm-foot-note"><i class="ri-information-line"></i> Leave a field empty to skip that filter</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-nx-outline" id="advSearchReset"><i class="ri-refresh-line me-1"></i> Reset</button>
                        <button type="button" class="btn-nx-primary" id="advSearchApply"><i class="ri-search-line me-1"></i> Apply Filters</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/lead-statuses.js') }}"></script>
@endpush
