<form class="ajax-form" method="POST" action="{{ route('admin.chart-of-accounts.update', $chartOfAccount->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Account</h5>
            <p>Update account: {{ $chartOfAccount->code }} - {{ $chartOfAccount->name }}</p>
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
                        <option value="{{ $id }}" {{ old('parent_id', $chartOfAccount->parent_id) == $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted">An account cannot be moved under itself or one of its own sub-accounts.</small>
            </div>
            <div class="fm-field">
                <label>Account Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" value="{{ old('code', $chartOfAccount->code) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Account Type <span class="req">*</span></label>
                <select name="account_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="asset" {{ old('account_type', $chartOfAccount->account_type) == 'asset' ? 'selected' : '' }}>Asset</option>
                    <option value="liability" {{ old('account_type', $chartOfAccount->account_type) == 'liability' ? 'selected' : '' }}>Liability</option>
                    <option value="equity" {{ old('account_type', $chartOfAccount->account_type) == 'equity' ? 'selected' : '' }}>Equity</option>
                    <option value="revenue" {{ old('account_type', $chartOfAccount->account_type) == 'revenue' ? 'selected' : '' }}>Revenue</option>
                    <option value="expense" {{ old('account_type', $chartOfAccount->account_type) == 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Account Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $chartOfAccount->name) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Account Type (Sub-classification)</label>
                <select name="account_type_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($accountTypes as $accountType)
                        <option value="{{ $accountType->id }}" {{ old('account_type_id', $chartOfAccount->account_type_id) == $accountType->id ? 'selected' : '' }}>{{ $accountType->name }} ({{ ucfirst($accountType->nature) }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Optional finer-grained classification (e.g. "Bank", "Fixed Asset") — its nature must match the Account Type selected above.</small>
            </div>
            <div class="fm-field">
                <label>Group / Header Account</label>
                <select name="is_group" class="form-select">
                    <option value="0" {{ old('is_group', $chartOfAccount->is_group) == '0' ? 'selected' : '' }}>No — postable account</option>
                    <option value="1" {{ old('is_group', $chartOfAccount->is_group) == '1' ? 'selected' : '' }}>Yes — group/header only</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $chartOfAccount->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $chartOfAccount->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $chartOfAccount->description) }}</textarea>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Updating...
            </button>
        </div>
    </div>
</form>
