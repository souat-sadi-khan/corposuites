<form class="ajax-form" method="POST" action="{{ route('admin.project-expenses.update', $projectExpense->id) }}" enctype="multipart/form-data">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Expense</h5>
            <p>{{ $projectExpense->project?->project_code }} &middot; {{ $projectExpense->approval_status_label }}</p>
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
                        <option value="{{ $project->id }}" {{ $projectExpense->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" value="{{ old('title', $projectExpense->title) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category <span class="req">*</span></label>
                <select name="expense_category" class="form-select" required>
                    @foreach (\App\Models\ProjectExpense::CATEGORIES as $category)
                        <option value="{{ $category }}" {{ $projectExpense->expense_category === $category ? 'selected' : '' }}>{{ ucfirst($category) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Amount <span class="req">*</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" value="{{ old('amount', $projectExpense->amount) }}" required>
            </div>
            <div class="fm-field">
                <label>Expense Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="expense_date" value="{{ old('expense_date', optional($projectExpense->expense_date)->toDateString()) }}" required>
            </div>
            <div class="fm-field">
                <label>Vendor</label>
                <select name="vendor_id" class="form-select select">
                    <option value="">Not paid to a vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ $projectExpense->vendor_id == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }} ({{ $vendor->vendor_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Incurred By</label>
                <select name="employee_id" class="form-select select">
                    <option value="">Not attributed to an employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $projectExpense->employee_id == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_billable" value="1" id="editExpenseBillable" {{ $projectExpense->is_billable ? 'checked' : '' }}>
                    <label class="form-check-label" for="editExpenseBillable">Billable to the client</label>
                </div>
            </div>
            @if ($projectExpense->receipt_path)
                <div class="fm-field fm-full">
                    <label>Current Receipt</label><br>
                    <a href="{{ asset('storage/' . $projectExpense->receipt_path) }}" target="_blank">View current receipt</a>
                </div>
            @endif
            <div class="fm-field fm-full">
                <label>Replace Receipt</label>
                <input type="file" class="form-control" name="receipt" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $projectExpense->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $projectExpense->notes) }}</textarea>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $projectExpense->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $projectExpense->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Use the Approve/Reject actions on the list to move this expense through review.
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
