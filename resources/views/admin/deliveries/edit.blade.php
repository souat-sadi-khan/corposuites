<form class="ajax-form delivery-form" method="POST" action="{{ route('admin.deliveries.update', $delivery->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Delivery</h5>
            <p>Update delivery: {{ $delivery->delivery_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Sales Order <span class="req">*</span></label>
                <select name="sales_order_id" class="form-select select" required>
                    <option value="">Select Sales Order</option>
                    @foreach($salesOrders as $order)
                        <option value="{{ $order->id }}" {{ old('sales_order_id', $delivery->sales_order_id) == $order->id ? 'selected' : '' }}>{{ $order->order_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Delivery Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="delivery_date" value="{{ old('delivery_date', optional($delivery->delivery_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Carrier</label>
                <input type="text" class="form-control" name="carrier" value="{{ old('carrier', $delivery->carrier) }}">
            </div>
            <div class="fm-field">
                <label>Tracking Number</label>
                <input type="text" class="form-control" name="tracking_number" value="{{ old('tracking_number', $delivery->tracking_number) }}">
            </div>
            <div class="fm-field">
                <label>Delivery Status</label>
                <select name="delivery_status" class="form-select">
                    @foreach(\App\Models\Delivery::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('delivery_status', $delivery->delivery_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $delivery->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $delivery->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $delivery->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Delivered Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm delivery-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="delivery-item-rows" data-existing='@json($delivery->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity]))'></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A delivery needs at least one item.
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

    <select class="d-none delivery-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
