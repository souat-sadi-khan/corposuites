<form class="ajax-form" method="POST" action="{{ route('admin.finance-bank-accounts.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Bank Account</h5>
            <p>Register a company/business bank account</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Bank Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="bank_name" placeholder="e.g., HBL, Standard Chartered" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Account Holder Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="account_name" placeholder="e.g., CorpoSuites Pvt Ltd" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Account Number <span class="req">*</span></label>
                <input type="text" class="form-control" name="account_number" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Branch</label>
                <input type="text" class="form-control" name="branch" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>IFSC / SWIFT Code</label>
                <input type="text" class="form-control" name="ifsc_swift_code" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Currency <span class="req">*</span></label>
                <input type="text" class="form-control" name="currency" value="USD" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Opening Balance <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="opening_balance" value="0" required>
            </div>
            <div class="fm-field">
                <label>Linked GL Account</label>
                <select name="chart_of_account_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($chartOfAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
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
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Optionally link this bank account to its corresponding Chart of Accounts entry for ledger reporting
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
