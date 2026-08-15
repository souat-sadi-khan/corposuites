<form class="ajax-form discount-rule-form" method="POST" action="{{ route('admin.discount-rules.update', $discountRule->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Discount Rule</h5>
            <p>Update rule: {{ $discountRule->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $discountRule->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Discount Type <span class="req">*</span></label>
                <select name="discount_type" class="form-select" required>
                    <option value="percentage" {{ old('discount_type', $discountRule->discount_type) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="fixed" {{ old('discount_type', $discountRule->discount_type) == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Value <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="value" value="{{ old('value', $discountRule->value) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Applies To <span class="req">*</span></label>
                <select name="scope_type" class="form-select discount-scope-select" required>
                    <option value="all" {{ old('scope_type', $discountRule->scope_type) == 'all' ? 'selected' : '' }}>All Products</option>
                    <option value="category" {{ old('scope_type', $discountRule->scope_type) == 'category' ? 'selected' : '' }}>Specific Category</option>
                    <option value="product" {{ old('scope_type', $discountRule->scope_type) == 'product' ? 'selected' : '' }}>Specific Product</option>
                </select>
            </div>
            <div class="fm-field fm-full discount-scope-category" style="display:none;">
                <label>Category <span class="req">*</span></label>
                <select name="category_id" class="form-select select">
                    <option value="">Select Category</option>
                    @foreach(\App\Models\Category::indentedOptions($categories) as $id => $label)
                        <option value="{{ $id }}" {{ old('category_id', $discountRule->category_id) == $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full discount-scope-product" style="display:none;">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" class="form-select select">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id', $discountRule->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Start Date</label>
                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', optional($discountRule->start_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>End Date</label>
                <input type="date" class="form-control" name="end_date" value="{{ old('end_date', optional($discountRule->end_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $discountRule->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $discountRule->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $discountRule->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Leave dates empty for an always-on discount
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
