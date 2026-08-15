<form class="ajax-form" method="POST" action="{{ route('admin.product-variants.update', $productVariant->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Product Variant</h5>
            <p>Update variant: {{ $productVariant->sku }}</p>
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
                        <option value="{{ $product->id }}" {{ old('product_id', $productVariant->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Variant SKU <span class="req">*</span></label>
                <input type="text" class="form-control" name="sku" value="{{ old('sku', $productVariant->sku) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Attribute Values <span class="req">*</span></label>
                @forelse($attributeValues as $attributeName => $values)
                    <div class="mb-2">
                        <small class="text-muted d-block mb-1">{{ $attributeName }}</small>
                        @foreach($values as $value)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="attribute_value_ids[]" value="{{ $value->id }}" id="av{{ $value->id }}" {{ in_array($value->id, old('attribute_value_ids', $selectedAttributeValueIds)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="av{{ $value->id }}">{{ $value->value }}</label>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-muted">No attribute values available.</p>
                @endforelse
            </div>
            <div class="fm-field fm-full">
                <label>Price Override</label>
                <input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ old('price', $productVariant->price) }}" placeholder="Leave empty to use the product's selling price">
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $productVariant->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $productVariant->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
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
