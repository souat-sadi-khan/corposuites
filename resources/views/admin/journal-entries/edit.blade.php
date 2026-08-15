<form class="ajax-form journal-entry-form" method="POST" action="{{ route('admin.journal-entries.update', $journalEntry->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Journal Entry</h5>
            <p>Update entry: {{ $journalEntry->entry_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Entry Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="entry_date" value="{{ old('entry_date', optional($journalEntry->entry_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Reference</label>
                <input type="text" class="form-control" name="reference" value="{{ old('reference', $journalEntry->reference) }}">
            </div>
            <div class="fm-field">
                <label>Entry Status</label>
                <select name="entry_status" class="form-select">
                    <option value="draft" {{ old('entry_status', $journalEntry->entry_status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="posted" {{ old('entry_status', $journalEntry->entry_status) == 'posted' ? 'selected' : '' }}>Posted</option>
                    <option value="cancelled" {{ old('entry_status', $journalEntry->entry_status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $journalEntry->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $journalEntry->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Narration</label>
                <textarea class="form-control" name="narration" rows="2">{{ old('narration', $journalEntry->narration) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Journal Lines <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm journal-entry-item-add">
                <i class="ri-add-line"></i> Add Line
            </button>
        </div>
        <div class="journal-entry-item-rows" data-existing='@json($journalEntry->items->map(fn($item) => ["chart_of_account_id" => $item->chart_of_account_id, "debit" => $item->debit, "credit" => $item->credit, "description" => $item->description]))'></div>

        <div class="d-flex justify-content-end mt-2">
            <div class="text-end">
                <div>Total Debit: <b class="je-debit-preview">0.00</b></div>
                <div>Total Credit: <b class="je-credit-preview">0.00</b></div>
                <div>Balance: <b class="je-balance-preview">0.00</b> <span class="badge je-balance-badge bg-secondary">Add lines</span></div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> At least 2 lines are required. Each line must be either a debit or a credit — total debits must equal total credits.
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

    <select class="d-none journal-entry-account-options">
        <option value="">Select Account</option>
        @foreach($chartOfAccounts as $account)
            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
        @endforeach
    </select>
</form>
