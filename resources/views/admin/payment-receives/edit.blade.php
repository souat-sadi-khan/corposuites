<form class="ajax-form payment-receive-form" method="POST" action="{{ route('admin.payment-receives.update', $paymentReceive->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Payment Receive</h5>
            <p>Update payment: {{ $paymentReceive->payment_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Customer <span class="req">*</span></label>
                <select name="customer_id" class="form-select select pr-customer-select" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', $paymentReceive->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Bank Account</label>
                <select name="finance_bank_account_id" class="form-select select">
                    <option value="">None (Cash)</option>
                    @foreach($financeBankAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('finance_bank_account_id', $paymentReceive->finance_bank_account_id) == $account->id ? 'selected' : '' }}>{{ $account->bank_name }} - {{ $account->account_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Payment Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', optional($paymentReceive->payment_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Payment Method <span class="req">*</span></label>
                <select name="payment_method" class="form-select" required>
                    @foreach(\App\Models\PaymentReceive::METHODS as $methodOption)
                        <option value="{{ $methodOption }}" {{ old('payment_method', $paymentReceive->payment_method) == $methodOption ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $methodOption)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Reference</label>
                <input type="text" class="form-control" name="reference" value="{{ old('reference', $paymentReceive->reference) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $paymentReceive->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $paymentReceive->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $paymentReceive->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Invoice Allocations <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm payment-receive-item-add">
                <i class="ri-add-line"></i> Add Invoice
            </button>
        </div>
        <p class="text-muted small mb-2">Only the selected customer's open invoices (plus any already allocated by this payment) are selectable.</p>
        <div class="payment-receive-item-rows" data-existing='@json($paymentReceive->items->map(fn($item) => ["sales_invoice_id" => $item->sales_invoice_id, "amount_allocated" => $item->amount_allocated]))'></div>

        <div class="d-flex justify-content-end mt-2">
            <div class="text-end">
                <div>Total Allocated: <b class="pr-amount-preview">0.00</b></div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. At least one invoice allocation is required.
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

    <select class="d-none payment-receive-invoice-options">
        <option value="">Select Invoice</option>
        @foreach($openInvoices as $invoice)
            <option value="{{ $invoice->id }}" data-customer-id="{{ $invoice->customer_id }}" data-balance="{{ $invoice->balance_due }}">{{ $invoice->invoice_number }} (Balance: {{ number_format($invoice->balance_due, 2) }})</option>
        @endforeach
    </select>
</form>
