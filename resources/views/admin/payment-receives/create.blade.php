<form class="ajax-form payment-receive-form" method="POST" action="{{ route('admin.payment-receives.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Receive Payment</h5>
            <p>Record a customer payment and allocate it against one or more open invoices</p>
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
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Bank Account</label>
                <select name="finance_bank_account_id" class="form-select select">
                    <option value="">None (Cash)</option>
                    @foreach($financeBankAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Payment Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Payment Method <span class="req">*</span></label>
                <select name="payment_method" class="form-select" required>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                    <option value="card">Card</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Reference</label>
                <input type="text" class="form-control" name="reference" placeholder="e.g., Cheque #, Transaction ID">
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
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes for this payment"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Invoice Allocations <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm payment-receive-item-add">
                <i class="ri-add-line"></i> Add Invoice
            </button>
        </div>
        <p class="text-muted small mb-2">Select a customer above first — only that customer's open (unpaid/partially-paid) invoices will be selectable.</p>
        <div class="payment-receive-item-rows"></div>

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
                <i class="ri-check-line me-1"></i> Record Payment
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>

    {{-- Invoice options source, consumed client-side by payment-receives.js. Each option carries the owning customer id and current balance due so rows can be filtered by the selected header customer and pre-fill a sensible allocation amount. No per-row AJAX. --}}
    <select class="d-none payment-receive-invoice-options">
        <option value="">Select Invoice</option>
        @foreach($openInvoices as $invoice)
            <option value="{{ $invoice->id }}" data-customer-id="{{ $invoice->customer_id }}" data-balance="{{ $invoice->balance_due }}">{{ $invoice->invoice_number }} (Balance: {{ number_format($invoice->balance_due, 2) }})</option>
        @endforeach
    </select>
</form>
