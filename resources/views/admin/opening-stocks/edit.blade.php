<form class="ajax-form opening-stock-form" method="POST" action="{{ route('admin.opening-stocks.update', $openingStock->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Opening Stock</h5>
            <p>Update entry: {{ $openingStock->entry_number }}</p>
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
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $openingStock->warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Warehouse Location</label>
                <select name="warehouse_location_id" class="form-select select">
                    <option value="">No Specific Location</option>
                    @foreach($warehouseLocations as $location)
                        <option value="{{ $location->id }}" {{ old('warehouse_location_id', $openingStock->warehouse_location_id) == $location->id ? 'selected' : '' }}>{{ $location->name }} ({{ $location->warehouse->name ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Opening Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="opening_date" value="{{ old('opening_date', optional($openingStock->opening_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Entry Status</label>
                <select name="entry_status" class="form-select">
                    @foreach(\App\Models\OpeningStock::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('entry_status', $openingStock->entry_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $openingStock->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $openingStock->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $openingStock->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Opening Balances <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm opening-stock-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="opening-stock-item-rows" data-existing='@json($openingStock->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "unit_cost" => $item->unit_cost, "notes" => $item->notes]))'></div>

        <div class="text-end mt-2">
            <div>Total Value: <b class="os-total-preview">0.00</b></div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. An opening stock entry needs at least one item.
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

    <select class="d-none opening-stock-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->cost_price }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
