<form class="ajax-form" method="POST" action="{{ route('admin.products.update', $product->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Product</h5>
            <p>Update product: {{ $product->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>SKU <span class="req">*</span></label>
                <input type="text" class="form-control" name="sku" value="{{ old('sku', $product->sku) }}" required>
            </div>
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $product->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Category</label>
                <select name="category_id" class="form-select select">
                    <option value="">No Category</option>
                    @foreach(\App\Models\Category::indentedOptions($categories) as $id => $label)
                        <option value="{{ $id }}" {{ old('category_id', $product->category_id) == $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Brand</label>
                <select name="brand_id" class="form-select select">
                    <option value="">No Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Unit</label>
                <select name="unit_id" class="form-select select">
                    <option value="">No Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->short_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Cost Price</label>
                <input type="number" step="0.01" min="0" class="form-control" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Selling Price</label>
                <input type="number" step="0.01" min="0" class="form-control" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $product->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $product->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
