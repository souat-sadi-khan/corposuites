<form class="ajax-form supplier-quotation-form" method="POST" action="{{ route('admin.supplier-quotations.update', $supplierQuotation->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Supplier Quotation</h5>
            <p>Update quotation: {{ $supplierQuotation->quotation_number }}</p>
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
                        <option value="{{ $vendor->id }}" {{ old('vendor_id', $supplierQuotation->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source RFQ</label>
                <select name="rfq_id" class="form-select select">
                    <option value="">No Source RFQ</option>
                    @foreach($rfqs as $rfq)
                        <option value="{{ $rfq->id }}" {{ old('rfq_id', $supplierQuotation->rfq_id) == $rfq->id ? 'selected' : '' }}>{{ $rfq->rfq_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Quotation Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="quotation_date" value="{{ old('quotation_date', optional($supplierQuotation->quotation_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Valid Until</label>
                <input type="date" class="form-control" name="valid_until" value="{{ old('valid_until', optional($supplierQuotation->valid_until)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Quotation Status</label>
                <select name="quotation_status" class="form-select">
                    @foreach(\App\Models\SupplierQuotation::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('quotation_status', $supplierQuotation->quotation_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $supplierQuotation->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $supplierQuotation->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $supplierQuotation->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Quoted Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm supplier-quotation-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="supplier-quotation-item-rows" data-existing='@json($supplierQuotation->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "unit_price" => $item->unit_price, "discount" => $item->discount]))'></div>

        <div class="text-end mt-2">
            <div>Subtotal: <b class="sq-subtotal-preview">0.00</b></div>
            <div>Discount: <b class="sq-discount-preview">0.00</b></div>
            <div>Grand Total: <b class="sq-grandtotal-preview">0.00</b></div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A quotation needs at least one item.
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

    <select class="d-none supplier-quotation-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
