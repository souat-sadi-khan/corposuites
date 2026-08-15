<form class="ajax-form purchase-invoice-form" method="POST" action="{{ route('admin.purchase-invoices.update', $purchaseInvoice->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Purchase Invoice</h5>
            <p>Update invoice: {{ $purchaseInvoice->invoice_number }} — Match status: {{ ucfirst($purchaseInvoice->match_status) }}</p>
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
                        <option value="{{ $vendor->id }}" {{ old('vendor_id', $purchaseInvoice->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Purchase Order</label>
                <select name="purchase_order_id" class="form-select select">
                    <option value="">No Source Order</option>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}" {{ old('purchase_order_id', $purchaseInvoice->purchase_order_id) == $purchaseOrder->id ? 'selected' : '' }}>{{ $purchaseOrder->po_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Goods Receipt</label>
                <select name="goods_receipt_id" class="form-select select">
                    <option value="">No Source Receipt</option>
                    @foreach($goodsReceipts as $goodsReceipt)
                        <option value="{{ $goodsReceipt->id }}" {{ old('goods_receipt_id', $purchaseInvoice->goods_receipt_id) == $goodsReceipt->id ? 'selected' : '' }}>{{ $goodsReceipt->receipt_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Invoice Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', optional($purchaseInvoice->invoice_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date" value="{{ old('due_date', optional($purchaseInvoice->due_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Amount Paid</label>
                <input type="number" step="0.01" min="0" class="form-control" name="amount_paid" value="{{ old('amount_paid', $purchaseInvoice->amount_paid) }}">
            </div>
            <div class="fm-field">
                <label>Invoice Status</label>
                <select name="invoice_status" class="form-select">
                    @foreach(\App\Models\PurchaseInvoice::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('invoice_status', $purchaseInvoice->invoice_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $purchaseInvoice->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $purchaseInvoice->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $purchaseInvoice->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Invoiced Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm purchase-invoice-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="purchase-invoice-item-rows" data-existing='@json($purchaseInvoice->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "unit_price" => $item->unit_price, "discount" => $item->discount]))'></div>

        <div class="text-end mt-2">
            <div>Subtotal: <b class="pinv-subtotal-preview">0.00</b></div>
            <div>Discount: <b class="pinv-discount-preview">0.00</b></div>
            <div>Grand Total: <b class="pinv-grandtotal-preview">0.00</b></div>
        </div>

        <div class="fm-foot-note mt-2">
            <i class="ri-information-line"></i> Match status is recalculated automatically on save.
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Updating...
            </button>
        </div>
    </div>

    <select class="d-none purchase-invoice-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
