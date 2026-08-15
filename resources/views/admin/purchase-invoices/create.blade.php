<form class="ajax-form purchase-invoice-form" method="POST" action="{{ route('admin.purchase-invoices.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Purchase Invoice</h5>
            <p>Record a vendor's billed invoice for matching against the order</p>
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
                <label>Source Purchase Order</label>
                <select name="purchase_order_id" class="form-select select">
                    <option value="">No Source Order</option>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}">{{ $purchaseOrder->po_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Goods Receipt</label>
                <select name="goods_receipt_id" class="form-select select">
                    <option value="">No Source Receipt</option>
                    @foreach($goodsReceipts as $goodsReceipt)
                        <option value="{{ $goodsReceipt->id }}">{{ $goodsReceipt->receipt_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Invoice Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="invoice_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date">
            </div>
            <div class="fm-field">
                <label>Amount Paid</label>
                <input type="number" step="0.01" min="0" class="form-control" name="amount_paid" value="0">
            </div>
            <div class="fm-field">
                <label>Invoice Status</label>
                <select name="invoice_status" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="paid">Paid</option>
                    <option value="disputed">Disputed</option>
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
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes for this invoice"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Invoiced Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm purchase-invoice-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="purchase-invoice-item-rows"></div>

        <div class="text-end mt-2">
            <div>Subtotal: <b class="pinv-subtotal-preview">0.00</b></div>
            <div>Discount: <b class="pinv-discount-preview">0.00</b></div>
            <div>Grand Total: <b class="pinv-grandtotal-preview">0.00</b></div>
        </div>

        <div class="fm-foot-note mt-2">
            <i class="ri-information-line"></i> Match status is calculated automatically on save by comparing invoiced quantities/prices against the linked Purchase Order and Goods Receipt.
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. An invoice needs at least one item.
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

    {{-- Product options source, consumed client-side by purchase-invoices.js. No per-row AJAX. --}}
    <select class="d-none purchase-invoice-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
