<form class="ajax-form product-bundle-form" method="POST" action="{{ route('admin.product-bundles.update', $productBundle->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Product Bundle</h5>
            <p>Update bundle: {{ $productBundle->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>SKU <span class="req">*</span></label>
                <input type="text" class="form-control" name="sku" value="{{ old('sku', $productBundle->sku) }}" required>
            </div>
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $productBundle->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Bundle Price</label>
                <input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ old('price', $productBundle->price) }}" placeholder="Leave empty to use sum of item prices">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $productBundle->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $productBundle->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $productBundle->description) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Bundle Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm bundle-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="bundle-item-rows" data-existing='@json($productBundle->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity]))'></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A bundle needs at least one item.
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

    <select class="d-none bundle-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
