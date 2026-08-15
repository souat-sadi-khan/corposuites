<form class="ajax-form reorder-level-form" method="POST" action="{{ route('admin.reorder-levels.update', $reorderLevel->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Reorder Level</h5>
            <p>Update reorder level for: {{ $reorderLevel->product->name ?? '-' }}</p>
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
                        <option value="{{ $product->id }}" {{ old('product_id', $reorderLevel->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Warehouse</label>
                <select name="warehouse_id" class="form-select select">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $reorderLevel->warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Reorder Level <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="reorder_level" value="{{ old('reorder_level', $reorderLevel->reorder_level) }}" required>
            </div>
            <div class="fm-field">
                <label>Reorder Quantity</label>
                <input type="number" step="0.01" min="0" class="form-control" name="reorder_quantity" value="{{ old('reorder_quantity', $reorderLevel->reorder_quantity) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $reorderLevel->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $reorderLevel->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $reorderLevel->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Leave Warehouse blank to apply this level across all warehouses
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
