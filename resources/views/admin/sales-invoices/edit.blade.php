<form class="ajax-form sales-invoice-form" method="POST" action="{{ route('admin.sales-invoices.update', $salesInvoice->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Sales Invoice</h5>
            <p>Update invoice: {{ $salesInvoice->invoice_number }}</p>
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
                        <option value="{{ $customer->id }}" {{ old('customer_id', $salesInvoice->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Assigned To</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ old('assigned_to', $salesInvoice->assigned_to) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Order</label>
                <select name="sales_order_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($salesOrders as $order)
                        <option value="{{ $order->id }}" {{ old('sales_order_id', $salesInvoice->sales_order_id) == $order->id ? 'selected' : '' }}>{{ $order->order_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Payment Term</label>
                <select name="payment_term_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($paymentTerms as $term)
                        <option value="{{ $term->id }}" {{ old('payment_term_id', $salesInvoice->payment_term_id) == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Invoice Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', optional($salesInvoice->invoice_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date" value="{{ old('due_date', optional($salesInvoice->due_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Amount Paid</label>
                <input type="number" step="0.01" min="0" class="form-control" name="amount_paid" value="{{ old('amount_paid', $salesInvoice->amount_paid) }}">
            </div>
            <div class="fm-field">
                <label>Invoice Status</label>
                <select name="invoice_status" class="form-select">
                    @foreach(\App\Models\SalesInvoice::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('invoice_status', $salesInvoice->invoice_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $salesInvoice->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $salesInvoice->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $salesInvoice->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Invoice Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm sales-invoice-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="sales-invoice-item-rows" data-existing='@json($salesInvoice->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "unit_price" => $item->unit_price, "discount" => $item->discount]))'></div>

        <div class="d-flex justify-content-end mt-2">
            <div class="text-end">
                <div>Subtotal: <b class="si-subtotal-preview">0.00</b></div>
                <div>Discount: <b class="si-discount-preview">0.00</b></div>
                <div>Grand Total: <b class="si-grandtotal-preview">0.00</b></div>
            </div>
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

    <select class="d-none sales-invoice-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
