<form class="ajax-form sales-order-form" method="POST" action="{{ route('admin.sales-orders.update', $salesOrder->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Sales Order</h5>
            <p>Update order: {{ $salesOrder->order_number }}</p>
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
                        <option value="{{ $customer->id }}" {{ old('customer_id', $salesOrder->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Assigned To</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ old('assigned_to', $salesOrder->assigned_to) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Quotation</label>
                <select name="sales_quotation_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($salesQuotations as $quotation)
                        <option value="{{ $quotation->id }}" {{ old('sales_quotation_id', $salesOrder->sales_quotation_id) == $quotation->id ? 'selected' : '' }}>{{ $quotation->quotation_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Payment Term</label>
                <select name="payment_term_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($paymentTerms as $term)
                        <option value="{{ $term->id }}" {{ old('payment_term_id', $salesOrder->payment_term_id) == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Order Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="order_date" value="{{ old('order_date', optional($salesOrder->order_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Expected Delivery Date</label>
                <input type="date" class="form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date', optional($salesOrder->expected_delivery_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Order Status</label>
                <select name="order_status" class="form-select">
                    @foreach(\App\Models\SalesOrder::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('order_status', $salesOrder->order_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $salesOrder->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $salesOrder->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $salesOrder->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Order Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm sales-order-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="sales-order-item-rows" data-existing='@json($salesOrder->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "unit_price" => $item->unit_price, "discount" => $item->discount]))'></div>

        <div class="d-flex justify-content-end mt-2">
            <div class="text-end">
                <div>Subtotal: <b class="so-subtotal-preview">0.00</b></div>
                <div>Discount: <b class="so-discount-preview">0.00</b></div>
                <div>Grand Total: <b class="so-grandtotal-preview">0.00</b></div>
            </div>
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

    <select class="d-none sales-order-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
