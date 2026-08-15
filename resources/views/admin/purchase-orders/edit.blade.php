<form class="ajax-form purchase-order-form" method="POST" action="{{ route('admin.purchase-orders.update', $purchaseOrder->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Purchase Order</h5>
            <p>Update order: {{ $purchaseOrder->po_number }}</p>
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
                        <option value="{{ $vendor->id }}" {{ old('vendor_id', $purchaseOrder->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Purchase Request</label>
                <select name="purchase_request_id" class="form-select select">
                    <option value="">No Source Request</option>
                    @foreach($purchaseRequests as $purchaseRequest)
                        <option value="{{ $purchaseRequest->id }}" {{ old('purchase_request_id', $purchaseOrder->purchase_request_id) == $purchaseRequest->id ? 'selected' : '' }}>{{ $purchaseRequest->request_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source RFQ</label>
                <select name="rfq_id" class="form-select select">
                    <option value="">No Source RFQ</option>
                    @foreach($rfqs as $rfq)
                        <option value="{{ $rfq->id }}" {{ old('rfq_id', $purchaseOrder->rfq_id) == $rfq->id ? 'selected' : '' }}>{{ $rfq->rfq_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Supplier Quotation</label>
                <select name="supplier_quotation_id" class="form-select select">
                    <option value="">No Source Quotation</option>
                    @foreach($supplierQuotations as $supplierQuotation)
                        <option value="{{ $supplierQuotation->id }}" {{ old('supplier_quotation_id', $purchaseOrder->supplier_quotation_id) == $supplierQuotation->id ? 'selected' : '' }}>{{ $supplierQuotation->quotation_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Order Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="order_date" value="{{ old('order_date', optional($purchaseOrder->order_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Expected Delivery Date</label>
                <input type="date" class="form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date', optional($purchaseOrder->expected_delivery_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Order Status</label>
                <select name="order_status" class="form-select">
                    @foreach(\App\Models\PurchaseOrder::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('order_status', $purchaseOrder->order_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $purchaseOrder->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $purchaseOrder->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $purchaseOrder->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Ordered Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm purchase-order-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="purchase-order-item-rows" data-existing='@json($purchaseOrder->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "unit_price" => $item->unit_price, "discount" => $item->discount]))'></div>

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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Updating...
            </button>
        </div>
    </div>

    <select class="d-none purchase-order-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
