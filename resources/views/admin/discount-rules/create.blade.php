<form class="ajax-form discount-rule-form" method="POST" action="{{ route('admin.discount-rules.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Discount Rule</h5>
            <p>Create a percentage or fixed-amount discount</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Summer Sale 10%" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Discount Type <span class="req">*</span></label>
                <select name="discount_type" class="form-select" required>
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Value <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="value" placeholder="e.g., 10" required>
            </div>
            <div class="fm-field fm-full">
                <label>Applies To <span class="req">*</span></label>
                <select name="scope_type" class="form-select discount-scope-select" required>
                    <option value="all">All Products</option>
                    <option value="category">Specific Category</option>
                    <option value="product">Specific Product</option>
                </select>
            </div>
            <div class="fm-field fm-full discount-scope-category" style="display:none;">
                <label>Category <span class="req">*</span></label>
                <select name="category_id" class="form-select select">
                    <option value="">Select Category</option>
                    @foreach(\App\Models\Category::indentedOptions($categories) as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full discount-scope-product" style="display:none;">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" class="form-select select">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Start Date</label>
                <input type="date" class="form-control" name="start_date">
            </div>
            <div class="fm-field">
                <label>End Date</label>
                <input type="date" class="form-control" name="end_date">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2" placeholder="Notes about this discount"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
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
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
