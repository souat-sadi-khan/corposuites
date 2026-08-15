<form class="ajax-form" method="POST" action="{{ route('admin.chart-of-accounts.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Account</h5>
            <p>Create a new chart of accounts entry</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Parent Account</label>
                <select name="parent_id" class="form-select select">
                    <option value="">None (Top Level)</option>
                    @foreach(\App\Models\ChartOfAccount::indentedOptions($accounts) as $id => $label)
                        <option value="{{ $id }}" {{ (string) $selectedParentId === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Account Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" placeholder="e.g., 1000" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Account Type <span class="req">*</span></label>
                <select name="account_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="revenue">Revenue</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Account Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Cash in Hand" required autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>Account Type (Sub-classification)</label>
                <select name="account_type_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($accountTypes as $accountType)
                        <option value="{{ $accountType->id }}">{{ $accountType->name }} ({{ ucfirst($accountType->nature) }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Optional finer-grained classification (e.g. "Bank", "Fixed Asset") — its nature must match the Account Type selected above.</small>
            </div>
            <div class="fm-field">
                <label>Group / Header Account</label>
                <select name="is_group" class="form-select">
                    <option value="0">No — postable account</option>
                    <option value="1">Yes — group/header only</option>
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
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Group/header accounts are for organizing the tree only — journal entries can only be posted to non-group accounts
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
