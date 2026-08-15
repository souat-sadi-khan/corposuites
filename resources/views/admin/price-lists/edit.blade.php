<form class="ajax-form price-list-form" method="POST" action="{{ route('admin.price-lists.update', $priceList->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Price List</h5>
            <p>Update price list: {{ $priceList->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $priceList->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Customer Group</label>
                <select name="customer_group_id" class="form-select select">
                    <option value="">All Customers</option>
                    @foreach($customerGroups as $group)
                        <option value="{{ $group->id }}" {{ old('customer_group_id', $priceList->customer_group_id) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $priceList->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $priceList->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $priceList->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Price List Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm price-list-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="price-list-item-rows" data-existing='@json($priceList->items->map(fn($item) => ["product_id" => $item->product_id, "price" => $item->price]))'></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A price list needs at least one item.
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

    <select class="d-none price-list-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
