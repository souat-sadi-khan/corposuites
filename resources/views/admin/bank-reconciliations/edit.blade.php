<form class="ajax-form bank-reconciliation-form" method="POST" action="{{ route('admin.bank-reconciliations.update', $bankReconciliation->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Bank Reconciliation</h5>
            <p>Update reconciliation: {{ $bankReconciliation->reconciliation_number }} — Variance: {{ number_format($bankReconciliation->variance, 2) }}</p>
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
                        <option value="{{ $bankAccount->id }}" {{ old('finance_bank_account_id', $bankReconciliation->finance_bank_account_id) == $bankAccount->id ? 'selected' : '' }}>{{ $bankAccount->bank_name }} ({{ $bankAccount->account_number }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Statement Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="statement_date" value="{{ old('statement_date', optional($bankReconciliation->statement_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Statement Opening Balance <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control brc-opening" name="statement_opening_balance" value="{{ old('statement_opening_balance', $bankReconciliation->statement_opening_balance) }}" required>
            </div>
            <div class="fm-field">
                <label>Statement Closing Balance <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control brc-closing" name="statement_closing_balance" value="{{ old('statement_closing_balance', $bankReconciliation->statement_closing_balance) }}" required>
            </div>
            <div class="fm-field">
                <label>Reconciliation Status</label>
                <select name="reconciliation_status" class="form-select">
                    @foreach(\App\Models\BankReconciliation::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('reconciliation_status', $bankReconciliation->reconciliation_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $bankReconciliation->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $bankReconciliation->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $bankReconciliation->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Matched Transactions</label>
            <button type="button" class="btn-nx-outline btn-sm bank-reconciliation-item-add">
                <i class="ri-add-line"></i> Add Transaction
            </button>
        </div>
        <div class="bank-reconciliation-item-rows" data-existing='@json($bankReconciliation->items->map(fn($item) => ["bank_transaction_id" => $item->bank_transaction_id]))'></div>

        <div class="text-end mt-2">
            <div>Computed Closing Balance: <b class="brc-computed-preview">0.00</b></div>
            <div>Variance: <b class="brc-variance-preview">0.00</b></div>
        </div>

        <div class="fm-foot-note mt-2">
            <i class="ri-information-line"></i> Only unreconciled transactions (plus this reconciliation's own) are listed. Variance is recalculated automatically on save.
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Updating...
            </button>
        </div>
    </div>

    <select class="d-none bank-reconciliation-transaction-options">
        <option value="">Select Transaction</option>
        @foreach($transactions as $transaction)
            <option value="{{ $transaction->id }}" data-type="{{ $transaction->transaction_type }}" data-amount="{{ $transaction->amount }}">
                {{ $transaction->transaction_date->format('d M, Y') }} — {{ ucfirst($transaction->transaction_type) }} — {{ number_format($transaction->amount, 2) }} {{ $transaction->reference ? '(' . $transaction->reference . ')' : '' }}
            </option>
        @endforeach
    </select>
</form>
