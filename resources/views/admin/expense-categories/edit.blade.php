<form class="ajax-form" method="POST" action="{{ route('admin.expense-categories.update', $expenseCategory->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Expense Category</h5>
            <p>Update: {{ $expenseCategory->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Category Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $expenseCategory->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Max Amount Per Claim</label>
                <input type="number" step="0.01" min="0" class="form-control" name="max_amount_per_claim" value="{{ old('max_amount_per_claim', $expenseCategory->max_amount_per_claim) }}" placeholder="Leave blank for no limit">
            </div>
            <div class="fm-field">
                <label>Receipt Required Above</label>
                <input type="number" step="0.01" min="0" class="form-control" name="receipt_required_above" value="{{ old('receipt_required_above', $expenseCategory->receipt_required_above) }}" placeholder="Leave blank to never require one">
            </div>
            <div class="fm-field fm-full">
                <label>GL Account (Chart of Accounts)</label>
                <select name="chart_of_account_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($chartOfAccounts as $account)
                        <option value="{{ $account->id }}" {{ (int) $expenseCategory->chart_of_account_id === $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="status" value="{{ $expenseCategory->status }}">
            {{-- <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $expenseCategory->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $expenseCategory->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div> --}}
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $expenseCategory->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <i class="ri-information-line"></i> Both policy fields are optional. The GL account must be a postable (non-group) Chart of Accounts entry.
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> <span class="text-danger">*</span> fields are required.
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
                Submitting...
            </button>
        </div>
    </div>
</form>
