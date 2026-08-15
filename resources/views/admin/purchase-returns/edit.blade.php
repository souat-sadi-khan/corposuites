<form class="ajax-form purchase-return-form" method="POST" action="{{ route('admin.purchase-returns.update', $purchaseReturn->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Purchase Return</h5>
            <p>Update return: {{ $purchaseReturn->return_number }}</p>
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
                        <option value="{{ $vendor->id }}" {{ old('vendor_id', $purchaseReturn->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Purchase Order</label>
                <select name="purchase_order_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}" {{ old('purchase_order_id', $purchaseReturn->purchase_order_id) == $purchaseOrder->id ? 'selected' : '' }}>{{ $purchaseOrder->po_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Goods Receipt</label>
                <select name="goods_receipt_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($goodsReceipts as $goodsReceipt)
                        <option value="{{ $goodsReceipt->id }}" {{ old('goods_receipt_id', $purchaseReturn->goods_receipt_id) == $goodsReceipt->id ? 'selected' : '' }}>{{ $goodsReceipt->receipt_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Return Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="return_date" value="{{ old('return_date', optional($purchaseReturn->return_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" value="{{ old('reason', $purchaseReturn->reason) }}">
            </div>
            <div class="fm-field">
                <label>Return Status</label>
                <select name="return_status" class="form-select">
                    @foreach(\App\Models\PurchaseReturn::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('return_status', $purchaseReturn->return_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $purchaseReturn->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $purchaseReturn->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $purchaseReturn->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Returned Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm purchase-return-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="purchase-return-item-rows" data-existing='@json($purchaseReturn->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "condition" => $item->condition, "notes" => $item->notes]))'></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A return needs at least one item.
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

    <select class="d-none purchase-return-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
