<form class="ajax-form" method="POST" action="{{ route('admin.account-types.update', $accountType->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Account Type</h5>
            <p>Update account type: {{ $accountType->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $accountType->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Nature <span class="req">*</span></label>
                <select name="nature" class="form-select" required>
                    <option value="">Select Nature</option>
                    <option value="asset" {{ old('nature', $accountType->nature) == 'asset' ? 'selected' : '' }}>Asset</option>
                    <option value="liability" {{ old('nature', $accountType->nature) == 'liability' ? 'selected' : '' }}>Liability</option>
                    <option value="equity" {{ old('nature', $accountType->nature) == 'equity' ? 'selected' : '' }}>Equity</option>
                    <option value="revenue" {{ old('nature', $accountType->nature) == 'revenue' ? 'selected' : '' }}>Revenue</option>
                    <option value="expense" {{ old('nature', $accountType->nature) == 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $accountType->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $accountType->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $accountType->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Nature must match the fixed accounting type of any Chart of Accounts entry it is assigned to
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
