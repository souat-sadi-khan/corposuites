<form class="ajax-form" method="POST" action="{{ route('admin.bank-transactions.update', $bankTransaction->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Bank Transaction</h5>
            <p>Update transaction #{{ $bankTransaction->id }}</p>
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
                        <option value="{{ $account->id }}" {{ old('finance_bank_account_id', $bankTransaction->finance_bank_account_id) == $account->id ? 'selected' : '' }}>{{ $account->bank_name }} - {{ $account->account_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Transaction Type <span class="req">*</span></label>
                <select name="transaction_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="deposit" {{ old('transaction_type', $bankTransaction->transaction_type) == 'deposit' ? 'selected' : '' }}>Deposit</option>
                    <option value="withdrawal" {{ old('transaction_type', $bankTransaction->transaction_type) == 'withdrawal' ? 'selected' : '' }}>Withdrawal</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Transaction Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="transaction_date" value="{{ old('transaction_date', optional($bankTransaction->transaction_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Amount <span class="req">*</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" value="{{ old('amount', $bankTransaction->amount) }}" required>
            </div>
            <div class="fm-field">
                <label>Reference</label>
                <input type="text" class="form-control" name="reference" value="{{ old('reference', $bankTransaction->reference) }}">
            </div>
            <div class="fm-field">
                <label>Linked Journal Entry</label>
                <select name="journal_entry_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($journalEntries as $entry)
                        <option value="{{ $entry->id }}" {{ old('journal_entry_id', $bankTransaction->journal_entry_id) == $entry->id ? 'selected' : '' }}>{{ $entry->entry_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Reconciled?</label>
                <select name="reconciled" class="form-select">
                    <option value="0" {{ old('reconciled', $bankTransaction->reconciled) == '0' ? 'selected' : '' }}>Pending</option>
                    <option value="1" {{ old('reconciled', $bankTransaction->reconciled) == '1' ? 'selected' : '' }}>Reconciled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Reconciled Date</label>
                <input type="date" class="form-control" name="reconciled_date" value="{{ old('reconciled_date', optional($bankTransaction->reconciled_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $bankTransaction->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $bankTransaction->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $bankTransaction->description) }}</textarea>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Updating...
            </button>
        </div>
    </div>
</form>
