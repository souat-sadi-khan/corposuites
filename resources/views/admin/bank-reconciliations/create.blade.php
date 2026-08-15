<form class="ajax-form bank-reconciliation-form" method="POST" action="{{ route('admin.bank-reconciliations.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Bank Reconciliation</h5>
            <p>Match bank statement transactions against recorded bank transactions</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Bank Account <span class="req">*</span></label>
                <select name="finance_bank_account_id" class="form-select select" required>
                    <option value="">Select Bank Account</option>
                    @foreach($bankAccounts as $bankAccount)
                        <option value="{{ $bankAccount->id }}">{{ $bankAccount->bank_name }} ({{ $bankAccount->account_number }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Statement Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="statement_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Statement Opening Balance <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control brc-opening" name="statement_opening_balance" value="0" required>
            </div>
            <div class="fm-field">
                <label>Statement Closing Balance <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control brc-closing" name="statement_closing_balance" value="0" required>
            </div>
            <div class="fm-field">
                <label>Reconciliation Status</label>
                <select name="reconciliation_status" class="form-select">
                    <option value="draft">Draft</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes for this reconciliation"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Matched Transactions</label>
            <button type="button" class="btn-nx-outline btn-sm bank-reconciliation-item-add">
                <i class="ri-add-line"></i> Add Transaction
            </button>
        </div>
        <div class="bank-reconciliation-item-rows"></div>

        <div class="text-end mt-2">
            <div>Computed Closing Balance: <b class="brc-computed-preview">0.00</b></div>
            <div>Variance: <b class="brc-variance-preview">0.00</b></div>
        </div>

        <div class="fm-foot-note mt-2">
            <i class="ri-information-line"></i> Only unreconciled transactions are listed. Adding a transaction here marks it as reconciled; removing it later unmarks it. Variance is calculated automatically on save.
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required.
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

    {{-- Transaction options source, consumed client-side by bank-reconciliations.js. No per-row AJAX. --}}
    <select class="d-none bank-reconciliation-transaction-options">
        <option value="">Select Transaction</option>
        @foreach($transactions as $transaction)
            <option value="{{ $transaction->id }}" data-type="{{ $transaction->transaction_type }}" data-amount="{{ $transaction->amount }}">
                {{ $transaction->transaction_date->format('d M, Y') }} — {{ ucfirst($transaction->transaction_type) }} — {{ number_format($transaction->amount, 2) }} {{ $transaction->reference ? '(' . $transaction->reference . ')' : '' }}
            </option>
        @endforeach
    </select>
</form>
