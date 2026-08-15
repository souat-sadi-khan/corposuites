@extends('admin.layout.app', ['title' => 'Bank Transactions', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="bankTransactionSearch" placeholder="Search Transactions">
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

        <select id="bankAccountFilter" class="form-select form-select-sm w-auto">
            <option value="">All Bank Accounts</option>
            @foreach($bankAccounts as $account)
                <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
            @endforeach
        </select>

        <select id="transactionTypeFilter" class="form-select form-select-sm w-auto">
            <option value="">All Types</option>
            <option value="deposit">Deposit</option>
            <option value="withdrawal">Withdrawal</option>
        </select>

        <select id="reconciledFilter" class="form-select form-select-sm w-auto">
            <option value="">All Reconciliation Status</option>
            <option value="1">Reconciled</option>
            <option value="0">Pending</option>
        </select>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.bank-transactions.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Transaction
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="bankTransactionTable" data-url="{{ route('admin.bank-transactions.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bank Account</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Reconciled</th>
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
    <script src="{{ asset('assets/system/js/pages/bank-transactions.js') }}"></script>
@endpush
