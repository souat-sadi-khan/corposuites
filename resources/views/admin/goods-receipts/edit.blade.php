<form class="ajax-form goods-receipt-form" method="POST" action="{{ route('admin.goods-receipts.update', $goodsReceipt->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Goods Receipt</h5>
            <p>Update receipt: {{ $goodsReceipt->receipt_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Purchase Order <span class="req">*</span></label>
                <select name="purchase_order_id" class="form-select select" required>
                    <option value="">Select Purchase Order</option>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}" {{ old('purchase_order_id', $goodsReceipt->purchase_order_id) == $purchaseOrder->id ? 'selected' : '' }}>{{ $purchaseOrder->po_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Received By</label>
                <select name="received_by" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ old('received_by', $goodsReceipt->received_by) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Received Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="received_date" value="{{ old('received_date', optional($goodsReceipt->received_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Receipt Status</label>
                <select name="receipt_status" class="form-select">
                    @foreach(\App\Models\GoodsReceipt::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('receipt_status', $goodsReceipt->receipt_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $goodsReceipt->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $goodsReceipt->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $goodsReceipt->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Received Products <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm goods-receipt-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="goods-receipt-item-rows" data-existing='@json($goodsReceipt->items->map(fn($item) => ["product_id" => $item->product_id, "quantity_received" => $item->quantity_received, "condition" => $item->condition, "notes" => $item->notes]))'></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A receipt needs at least one item.
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

    <select class="d-none goods-receipt-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
