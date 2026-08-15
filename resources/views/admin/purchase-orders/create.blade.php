<form class="ajax-form purchase-order-form" method="POST" action="{{ route('admin.purchase-orders.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Purchase Order</h5>
            <p>Place an order with a vendor</p>
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
                <label>Source Purchase Request</label>
                <select name="purchase_request_id" class="form-select select">
                    <option value="">No Source Request</option>
                    @foreach($purchaseRequests as $purchaseRequest)
                        <option value="{{ $purchaseRequest->id }}">{{ $purchaseRequest->request_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source RFQ</label>
                <select name="rfq_id" class="form-select select">
                    <option value="">No Source RFQ</option>
                    @foreach($rfqs as $rfq)
                        <option value="{{ $rfq->id }}">{{ $rfq->rfq_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Supplier Quotation</label>
                <select name="supplier_quotation_id" class="form-select select">
                    <option value="">No Source Quotation</option>
                    @foreach($supplierQuotations as $supplierQuotation)
                        <option value="{{ $supplierQuotation->id }}">{{ $supplierQuotation->quotation_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Order Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="order_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Expected Delivery Date</label>
                <input type="date" class="form-control" name="expected_delivery_date">
            </div>
            <div class="fm-field">
                <label>Order Status</label>
                <select name="order_status" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="partially_received">Partially Received</option>
                    <option value="completed">Completed</option>
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
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes for this order"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Ordered Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm purchase-order-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="purchase-order-item-rows"></div>

        <div class="text-end mt-2">
            <div>Subtotal: <b class="po-subtotal-preview">0.00</b></div>
            <div>Discount: <b class="po-discount-preview">0.00</b></div>
            <div>Grand Total: <b class="po-grandtotal-preview">0.00</b></div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. An order needs at least one item.
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

    {{-- Product options source, consumed client-side by purchase-orders.js. No per-row AJAX. --}}
    <select class="d-none purchase-order-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
