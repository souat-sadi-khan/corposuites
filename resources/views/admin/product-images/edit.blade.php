<form class="ajax-form" method="POST" action="{{ route('admin.product-images.update', $productImage->id) }}" enctype="multipart/form-data">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Product Image</h5>
            <p>Update image for: {{ $productImage->product->name ?? '-' }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <img src="{{ asset('storage/' . $productImage->image_path) }}" alt="current image" style="max-width:150px;border-radius:8px;">
            </div>
            <div class="fm-field fm-full">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" class="form-select select" required>
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id', $productImage->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Replace Image</label>
                <input type="file" class="form-control" name="image" accept="image/*">
                <small class="text-muted">Leave empty to keep the current image.</small>
            </div>
            <div class="fm-field">
                <label>Sort Order</label>
                <input type="number" min="0" class="form-control" name="sort_order" value="{{ old('sort_order', $productImage->sort_order) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $productImage->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $productImage->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="isPrimary" {{ old('is_primary', $productImage->is_primary) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isPrimary">Set as primary image</label>
                </div>
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
