<form class="ajax-form" method="POST" action="{{ route('admin.project-invoices.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Create Invoice</h5>
            <p>Bill a project's client for billable time and approved expenses</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Project <span class="req">*</span></label>
                <select name="project_id" class="form-select select pinv-project-select" required>
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Invoice Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="invoice_date" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date">
            </div>
            <div class="fm-field">
                <label>Invoice State <span class="req">*</span></label>
                <select name="invoice_status" class="form-select" required>
                    @foreach (\App\Models\ProjectInvoice::STATUSES as $invoiceStatus)
                        <option value="{{ $invoiceStatus }}" {{ $invoiceStatus === 'draft' ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $invoiceStatus)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Discount Amount</label>
                <input type="number" step="0.01" min="0" class="form-control pinv-discount-input" name="discount_amount" value="0">
            </div>
            <div class="fm-field">
                <label>Tax Amount</label>
                <input type="number" step="0.01" min="0" class="form-control pinv-tax-input" name="tax_amount" value="0">
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

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Invoice Lines <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm pinv-item-add">
                <i class="ri-add-line"></i> Add Line
            </button>
        </div>
        <p class="text-muted small mb-2">Select a project above first — only that project's unbilled time entries and approved expenses can be picked as a line's source.</p>
        <div class="pinv-item-rows"></div>

        <div class="d-flex justify-content-end mt-3">
            <div class="text-end" style="min-width:220px;">
                <div>Subtotal: <b class="pinv-subtotal-preview">0.00</b></div>
                <div>Discount: <b class="pinv-discount-preview">0.00</b></div>
                <div>Tax: <b class="pinv-tax-preview">0.00</b></div>
                <div class="border-top pt-1 mt-1">Grand Total: <b class="pinv-grandtotal-preview">0.00</b></div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Picking a time entry or expense auto-fills its line but stays editable. A source can only be billed once — it's removed from the picker after this invoice is saved, and freed again if the invoice is cancelled.
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

    {{-- Pickable sources, consumed client-side by project-invoices.js. Each option
         carries the owning project id plus enough data to auto-fill a line
         (description/quantity/unit price) — no per-row AJAX. --}}
    <select class="d-none pinv-time-entry-options">
        <option value="">Select time entry</option>
        @foreach ($pickableTimeEntries as $entry)
            <option value="{{ $entry->id }}"
                data-project-id="{{ $entry->project_id }}"
                data-description="{{ $entry->task ? $entry->task->title : $entry->project->name }} — {{ $entry->work_date->format('d M Y') }}"
                data-quantity="{{ $entry->hours }}">
                {{ $entry->work_date->format('d M Y') }} · {{ $entry->task ? $entry->task->title : 'General' }} ({{ number_format($entry->hours, 2) }}h)
            </option>
        @endforeach
    </select>
    <select class="d-none pinv-expense-options">
        <option value="">Select expense</option>
        @foreach ($pickableExpenses as $expense)
            <option value="{{ $expense->id }}"
                data-project-id="{{ $expense->project_id }}"
                data-description="{{ $expense->title }}"
                data-unit-price="{{ $expense->amount }}">
                {{ $expense->expense_date->format('d M Y') }} · {{ $expense->title }} ({{ number_format($expense->amount, 2) }})
            </option>
        @endforeach
    </select>
</form>
