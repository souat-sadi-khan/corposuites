<form class="ajax-form sales-return-form" method="POST" action="{{ route('admin.sales-returns.update', $salesReturn->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Sales Return</h5>
            <p>Update return: {{ $salesReturn->return_number }}</p>
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
                        <option value="{{ $customer->id }}" {{ old('customer_id', $salesReturn->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Order</label>
                <select name="sales_order_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($salesOrders as $order)
                        <option value="{{ $order->id }}" {{ old('sales_order_id', $salesReturn->sales_order_id) == $order->id ? 'selected' : '' }}>{{ $order->order_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Delivery</label>
                <select name="delivery_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($deliveries as $delivery)
                        <option value="{{ $delivery->id }}" {{ old('delivery_id', $salesReturn->delivery_id) == $delivery->id ? 'selected' : '' }}>{{ $delivery->delivery_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Return Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="return_date" value="{{ old('return_date', optional($salesReturn->return_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" value="{{ old('reason', $salesReturn->reason) }}">
            </div>
            <div class="fm-field">
                <label>Return Status</label>
                <select name="return_status" class="form-select">
                    @foreach(\App\Models\SalesReturn::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('return_status', $salesReturn->return_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $salesReturn->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $salesReturn->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $salesReturn->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Returned Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm sales-return-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="sales-return-item-rows" data-existing='@json($salesReturn->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "condition" => $item->condition, "notes" => $item->notes]))'></div>
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

    <select class="d-none sales-return-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
