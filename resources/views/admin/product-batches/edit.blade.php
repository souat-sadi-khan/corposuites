<form class="ajax-form product-batch-form" method="POST" action="{{ route('admin.product-batches.update', $productBatch->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Product Batch</h5>
            <p>Update batch: {{ $productBatch->batch_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" class="form-select select" required>
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id', $productBatch->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Warehouse <span class="req">*</span></label>
                <select name="warehouse_id" class="form-select select" required>
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $productBatch->warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Batch Number <span class="req">*</span></label>
                <input type="text" class="form-control" name="batch_number" value="{{ old('batch_number', $productBatch->batch_number) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Quantity <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="quantity" value="{{ old('quantity', $productBatch->quantity) }}" required>
            </div>
            <div class="fm-field">
                <label>Manufacturing Date</label>
                <input type="date" class="form-control" name="manufacturing_date" value="{{ old('manufacturing_date', optional($productBatch->manufacturing_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Expiry Date</label>
                <input type="date" class="form-control" name="expiry_date" value="{{ old('expiry_date', optional($productBatch->expiry_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Unit Cost</label>
                <input type="number" step="0.01" min="0" class="form-control" name="unit_cost" value="{{ old('unit_cost', $productBatch->unit_cost) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $productBatch->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $productBatch->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $productBatch->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required
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
</form>
