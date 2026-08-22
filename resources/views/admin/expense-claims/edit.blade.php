<form class="ajax-form" method="POST" action="{{ route('admin.expense-claims.update', $expenseClaim->id) }}" enctype="multipart/form-data">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Expense Claim</h5>
            <p>Update expense claim record</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" data-placeholder="Select Employee" class="form-select select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}"  value="{{ $employee->id }}" {{ old('employee_id', $expenseClaim->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Category <span class="req">*</span></label>
                <select name="expense_category_id" data-placeholder="Select Category" class="form-select select" required>
                    <option value="">Select Category</option>
                    @foreach($expenseCategories as $category)
                        <option data-desc="{{ $category->description }}" value="{{ $category->id }}" {{ old('expense_category_id', $expenseClaim->expense_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @if(!$expenseClaim->expense_category_id && $expenseClaim->category_legacy)
                    <small class="text-muted">Previously recorded as: {{ $expenseClaim->category_legacy }}</small>
                @endif
            </div>
            <div class="fm-field">
                <label>Amount <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control" name="amount" min="0" value="{{ old('amount', $expenseClaim->amount) }}" required>
            </div>
            <div class="fm-field">
                <label>Expense Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="expense_date" value="{{ old('expense_date', $expenseClaim->expense_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('status', $expenseClaim->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $expenseClaim->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="fm-field">
                <label>Reimbursement Status</label>
                <select name="payment_status" data-minimum-results-for-search="Infinity" class="form-select select">
                    <option value="unpaid" {{ old('payment_status', $expenseClaim->payment_status) == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="paid" {{ old('payment_status', $expenseClaim->payment_status) == 'paid' ? 'selected' : '' }}>Reimbursed</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Reimbursement Method</label>
                <select name="reimbursement_method" data-minimum-results-for-search="Infinity" class="form-selects select">
                    <option value="">—</option>
                    @foreach(\App\Models\ExpenseClaim::REIMBURSEMENT_METHODS as $method)
                        <option value="{{ $method }}" {{ old('reimbursement_method', $expenseClaim->reimbursement_method) == $method ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($method)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Payment Date</label>
                <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', $expenseClaim->payment_date?->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Receipt</label>
                @if($expenseClaim->receipt_path)
                    <div class="mb-2">
                        <a href="{{ asset('storage/' . $expenseClaim->receipt_path) }}" target="_blank">View current receipt</a>
                    </div>
                @endif
                <input type="file" class="form-control" name="receipt">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $expenseClaim->description) }}</textarea>
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
