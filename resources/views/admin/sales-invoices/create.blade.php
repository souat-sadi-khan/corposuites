<form class="ajax-form sales-invoice-form" method="POST" action="{{ route('admin.sales-invoices.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Sales Invoice</h5>
            <p>Bill a customer for product line items</p>
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
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Assigned To</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Order</label>
                <select name="sales_order_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($salesOrders as $order)
                        <option value="{{ $order->id }}">{{ $order->order_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Payment Term</label>
                <select name="payment_term_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($paymentTerms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }}</option>
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
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="partially_paid">Partially Paid</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
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
            <label class="mb-0">Invoice Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm sales-invoice-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="sales-invoice-item-rows"></div>

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
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>

    {{-- Product options source, consumed client-side by sales-invoices.js. No per-row AJAX. --}}
    <select class="d-none sales-invoice-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
