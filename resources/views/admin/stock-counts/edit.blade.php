<form class="ajax-form stock-count-form" method="POST" action="{{ route('admin.stock-counts.update', $stockCount->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Stock Count</h5>
            <p>Update count: {{ $stockCount->count_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Warehouse <span class="req">*</span></label>
                <select name="warehouse_id" class="form-select select" required>
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $stockCount->warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Warehouse Location</label>
                <select name="warehouse_location_id" class="form-select select">
                    <option value="">No Specific Location</option>
                    @foreach($warehouseLocations as $location)
                        <option value="{{ $location->id }}" {{ old('warehouse_location_id', $stockCount->warehouse_location_id) == $location->id ? 'selected' : '' }}>{{ $location->name }} ({{ $location->warehouse->name ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Count Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="count_date" value="{{ old('count_date', optional($stockCount->count_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Count Status</label>
                <select name="count_status" class="form-select">
                    @foreach(\App\Models\StockCount::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('count_status', $stockCount->count_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $stockCount->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $stockCount->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $stockCount->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Counted Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm stock-count-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="stock-count-item-rows" data-existing='@json($stockCount->items->map(fn($item) => ["product_id" => $item->product_id, "system_quantity" => $item->system_quantity, "counted_quantity" => $item->counted_quantity, "notes" => $item->notes]))'></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A stock count needs at least one item. System quantity is optional — leave blank if not yet known.
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

    <select class="d-none stock-count-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
