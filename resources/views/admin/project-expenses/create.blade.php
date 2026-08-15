<form class="ajax-form" method="POST" action="{{ route('admin.project-expenses.store') }}" enctype="multipart/form-data">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Expense</h5>
            <p>Record a cost incurred against a project</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Project <span class="req">*</span></label>
                <select name="project_id" class="form-select select" required>
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" placeholder="e.g., Cement delivery, Site visit taxi" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category <span class="req">*</span></label>
                <select name="expense_category" class="form-select" required>
                    @foreach (\App\Models\ProjectExpense::CATEGORIES as $category)
                        <option value="{{ $category }}" {{ $category === 'other' ? 'selected' : '' }}>{{ ucfirst($category) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Amount <span class="req">*</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
            </div>
            <div class="fm-field">
                <label>Expense Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="expense_date" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Vendor</label>
                <select name="vendor_id" class="form-select select">
                    <option value="">Not paid to a vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }} ({{ $vendor->vendor_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Incurred By</label>
                <select name="employee_id" class="form-select select">
                    <option value="">Not attributed to an employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_billable" value="1" id="createExpenseBillable" checked>
                    <label class="form-check-label" for="createExpenseBillable">Billable to the client</label>
                </div>
            </div>
            <div class="fm-field fm-full">
                <label>Receipt</label>
                <input type="file" class="form-control" name="receipt" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> New expenses start Pending. "Incurred By" is for attribution only — an employee wanting reimbursement still files a regular HRM Expense Claim.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Save
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
