@php
    $existingInvoiceItems = $projectInvoice->items->map(function ($item) {
        return [
            'source_type' => $item->source_type,
            'project_time_entry_id' => $item->project_time_entry_id,
            'project_expense_id' => $item->project_expense_id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ];
    })->values();
@endphp
<form class="ajax-form" method="POST" action="{{ route('admin.project-invoices.update', $projectInvoice->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Invoice</h5>
            <p>{{ $projectInvoice->invoice_number }} &middot; {{ $projectInvoice->invoice_status_label }}</p>
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
                        <option value="{{ $project->id }}" {{ $projectInvoice->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Invoice Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', optional($projectInvoice->invoice_date)->toDateString()) }}" required>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date" value="{{ old('due_date', optional($projectInvoice->due_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Invoice State <span class="req">*</span></label>
                <select name="invoice_status" class="form-select" required>
                    @foreach (\App\Models\ProjectInvoice::STATUSES as $invoiceStatus)
                        <option value="{{ $invoiceStatus }}" {{ $projectInvoice->invoice_status === $invoiceStatus ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $invoiceStatus)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Discount Amount</label>
                <input type="number" step="0.01" min="0" class="form-control pinv-discount-input" name="discount_amount" value="{{ old('discount_amount', $projectInvoice->discount_amount) }}">
            </div>
            <div class="fm-field">
                <label>Tax Amount</label>
                <input type="number" step="0.01" min="0" class="form-control pinv-tax-input" name="tax_amount" value="{{ old('tax_amount', $projectInvoice->tax_amount) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $projectInvoice->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $projectInvoice->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $projectInvoice->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Invoice Lines <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm pinv-item-add">
                <i class="ri-add-line"></i> Add Line
            </button>
        </div>
        <div class="pinv-item-rows" data-existing='@json($existingInvoiceItems)'></div>

        <div class="d-flex justify-content-end mt-3">
            <div class="text-end" style="min-width:220px;">
                <div>Subtotal: <b class="pinv-subtotal-preview">{{ number_format($projectInvoice->subtotal, 2) }}</b></div>
                <div>Discount: <b class="pinv-discount-preview">{{ number_format($projectInvoice->discount_amount, 2) }}</b></div>
                <div>Tax: <b class="pinv-tax-preview">{{ number_format($projectInvoice->tax_amount, 2) }}</b></div>
                <div class="border-top pt-1 mt-1">Grand Total: <b class="pinv-grandtotal-preview">{{ number_format($projectInvoice->grand_total, 2) }}</b></div>
                <div class="text-muted small">Paid so far: {{ number_format($projectInvoice->amount_paid, 2) }} &middot; Balance: {{ number_format($projectInvoice->balance_due, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> A paid or cancelled invoice can no longer be edited. Use the Mark Sent / Record Payment / Cancel actions on the list instead.
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
