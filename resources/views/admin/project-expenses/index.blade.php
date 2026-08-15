@extends('admin.layout.app', ['title' => 'Project Expenses', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="expenseSearch" placeholder="Search Expenses">
        </div>

        <select id="projectFilter" class="form-select form-select-sm w-auto">
            <option value="">All Projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <select id="categoryFilter" class="form-select form-select-sm w-auto">
            <option value="">All Categories</option>
            @foreach (\App\Models\ProjectExpense::CATEGORIES as $category)
                <option value="{{ $category }}">{{ ucfirst($category) }}</option>
            @endforeach
        </select>

        <select id="approvalFilter" class="form-select form-select-sm w-auto">
            <option value="">All Approval States</option>
            @foreach (\App\Models\ProjectExpense::APPROVAL_STATUSES as $approvalStatus)
                <option value="{{ $approvalStatus }}">{{ ucfirst($approvalStatus) }}</option>
            @endforeach
        </select>

        <select id="billableFilter" class="form-select form-select-sm w-auto">
            <option value="">Billable & Non-billable</option>
            <option value="1">Billable Only</option>
            <option value="0">Non-billable Only</option>
        </select>

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

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.project-expenses.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Expense
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="expenseTable" data-url="{{ route('admin.project-expenses.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Expense</th>
                        <th>Project</th>
                        <th>Paid To</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Receipt</th>
                        <th>Approval</th>
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
    <script src="{{ asset('assets/system/js/pages/project-expenses.js') }}"></script>
@endpush
