<form class="ajax-form debit-note-form" method="POST" action="{{ route('admin.debit-notes.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Debit Note</h5>
            <p>Reduce what is owed to a vendor</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Vendor <span class="req">*</span></label>
                <select name="vendor_id" class="form-select select" required>
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Invoice</label>
                <select name="purchase_invoice_id" class="form-select select">
                    <option value="">No Source Invoice</option>
                    @foreach($purchaseInvoices as $purchaseInvoice)
                        <option value="{{ $purchaseInvoice->id }}">{{ $purchaseInvoice->invoice_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Debit Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="debit_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" placeholder="e.g., Damaged goods, Pricing error">
            </div>
            <div class="fm-field">
                <label>Debit Status</label>
                <select name="debit_status" class="form-select">
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
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes for this debit note"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Debited Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm debit-note-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="debit-note-item-rows"></div>

        <div class="text-end mt-2">
            <div>Subtotal: <b class="dbn-subtotal-preview">0.00</b></div>
            <div>Discount: <b class="dbn-discount-preview">0.00</b></div>
            <div>Grand Total: <b class="dbn-grandtotal-preview">0.00</b></div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A debit note needs at least one item.
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

    {{-- Product options source, consumed client-side by debit-notes.js. No per-row AJAX. --}}
    <select class="d-none debit-note-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
