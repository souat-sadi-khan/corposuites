<form class="ajax-form" method="POST" action="{{ route('admin.bank-transactions.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Bank Transaction</h5>
            <p>Record a deposit or withdrawal against a bank account</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Bank Account <span class="req">*</span></label>
                <select name="finance_bank_account_id" class="form-select select" required>
                    <option value="">Select Bank Account</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Transaction Type <span class="req">*</span></label>
                <select name="transaction_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="deposit">Deposit</option>
                    <option value="withdrawal">Withdrawal</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Transaction Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="transaction_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Amount <span class="req">*</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
            </div>
            <div class="fm-field">
                <label>Reference</label>
                <input type="text" class="form-control" name="reference" placeholder="e.g., cheque/transfer number">
            </div>
            <div class="fm-field">
                <label>Linked Journal Entry</label>
                <select name="journal_entry_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($journalEntries as $entry)
                        <option value="{{ $entry->id }}">{{ $entry->entry_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Reconciled?</label>
                <select name="reconciled" class="form-select">
                    <option value="0">Pending</option>
                    <option value="1">Reconciled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Reconciled Date</label>
                <input type="date" class="form-control" name="reconciled_date">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
