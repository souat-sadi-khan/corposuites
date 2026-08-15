<form class="ajax-form" method="POST" action="{{ route('admin.product-prices.update', $productPrice->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Product Price</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" class="form-select select" required>
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id', $productPrice->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Price Tier <span class="req">*</span></label>
                <select name="price_tier_id" class="form-select select" required>
                    <option value="">Select Tier</option>
                    @foreach($priceTiers as $tier)
                        <option value="{{ $tier->id }}" {{ old('price_tier_id', $productPrice->price_tier_id) == $tier->id ? 'selected' : '' }}>{{ $tier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Price <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ old('price', $productPrice->price) }}" required>
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
