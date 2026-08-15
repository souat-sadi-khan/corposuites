<form class="ajax-form" method="POST" action="{{ route('admin.products.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Product</h5>
            <p>Create a new product</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>SKU <span class="req">*</span></label>
                <input type="text" class="form-control" name="sku" placeholder="e.g., PRD-0001" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Product name" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category</label>
                <select name="category_id" class="form-select select">
                    <option value="">No Category</option>
                    @foreach(\App\Models\Category::indentedOptions($categories) as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Brand</label>
                <select name="brand_id" class="form-select select">
                    <option value="">No Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Unit</label>
                <select name="unit_id" class="form-select select">
                    <option value="">No Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Cost Price</label>
                <input type="number" step="0.01" min="0" class="form-control" name="cost_price" placeholder="0.00">
            </div>
            <div class="fm-field fm-full">
                <label>Selling Price</label>
                <input type="number" step="0.01" min="0" class="form-control" name="selling_price" placeholder="0.00">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Product description"></textarea>
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
            <i class="ri-information-line"></i> Fields marked with * are required
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
