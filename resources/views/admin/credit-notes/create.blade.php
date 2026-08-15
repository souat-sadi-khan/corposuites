<form class="ajax-form credit-note-form" method="POST" action="{{ route('admin.credit-notes.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Credit Note</h5>
            <p>Issue a credit against a customer, optionally linked to an invoice</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Customer <span class="req">*</span></label>
                <select name="customer_id" class="form-select select" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Invoice</label>
                <select name="sales_invoice_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($salesInvoices as $invoice)
                        <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Credit Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="credit_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" placeholder="e.g., Returned goods, Pricing error">
            </div>
            <div class="fm-field">
                <label>Credit Status</label>
                <select name="credit_status" class="form-select">
                    <option value="draft">Draft</option>
                    <option value="issued">Issued</option>
                    <option value="applied">Applied</option>
                    <option value="cancelled">Cancelled</option>
                </select>
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
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes for this credit note"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Credit Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm credit-note-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="credit-note-item-rows"></div>

        <div class="d-flex justify-content-end mt-2">
            <div class="text-end">
                <div>Subtotal: <b class="cn-subtotal-preview">0.00</b></div>
                <div>Discount: <b class="cn-discount-preview">0.00</b></div>
                <div>Grand Total: <b class="cn-grandtotal-preview">0.00</b></div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A credit note needs at least one item.
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

    {{-- Product options source, consumed client-side by credit-notes.js. No per-row AJAX. --}}
    <select class="d-none credit-note-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
