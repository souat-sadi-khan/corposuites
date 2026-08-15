<form class="ajax-form" method="POST" action="{{ route('admin.expense-claims.store') }}" enctype="multipart/form-data">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Expense Claim</h5>
            <p>Submit a new expense claim for an employee</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Category <span class="req">*</span></label>
                <input type="text" class="form-control" name="category" placeholder="e.g., Travel, Meals" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Amount <span class="req">*</span></label>
                <input type="number" step="0.01" class="form-control" name="amount" min="0" value="0" required>
            </div>
            <div class="fm-field">
                <label>Expense Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="expense_date" required>
            </div>
            <div class="fm-field">
                <label>Receipt</label>
                <input type="file" class="form-control" name="receipt">
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3"></textarea>
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
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
