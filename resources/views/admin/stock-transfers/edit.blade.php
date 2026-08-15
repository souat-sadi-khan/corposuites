<form class="ajax-form stock-transfer-form" method="POST" action="{{ route('admin.stock-transfers.update', $stockTransfer->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Stock Transfer</h5>
            <p>Update transfer: {{ $stockTransfer->transfer_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>From Warehouse <span class="req">*</span></label>
                <select name="from_warehouse_id" class="form-select select" required>
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('from_warehouse_id', $stockTransfer->from_warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>From Location</label>
                <select name="from_warehouse_location_id" class="form-select select">
                    <option value="">No Specific Location</option>
                    @foreach($warehouseLocations as $location)
                        <option value="{{ $location->id }}" {{ old('from_warehouse_location_id', $stockTransfer->from_warehouse_location_id) == $location->id ? 'selected' : '' }}>{{ $location->name }} ({{ $location->warehouse->name ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>To Warehouse <span class="req">*</span></label>
                <select name="to_warehouse_id" class="form-select select" required>
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('to_warehouse_id', $stockTransfer->to_warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>To Location</label>
                <select name="to_warehouse_location_id" class="form-select select">
                    <option value="">No Specific Location</option>
                    @foreach($warehouseLocations as $location)
                        <option value="{{ $location->id }}" {{ old('to_warehouse_location_id', $stockTransfer->to_warehouse_location_id) == $location->id ? 'selected' : '' }}>{{ $location->name }} ({{ $location->warehouse->name ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Transfer Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="transfer_date" value="{{ old('transfer_date', optional($stockTransfer->transfer_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" value="{{ old('reason', $stockTransfer->reason) }}">
            </div>
            <div class="fm-field">
                <label>Transfer Status</label>
                <select name="transfer_status" class="form-select">
                    @foreach(\App\Models\StockTransfer::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('transfer_status', $stockTransfer->transfer_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $stockTransfer->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $stockTransfer->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $stockTransfer->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Transferred Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm stock-transfer-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="stock-transfer-item-rows" data-existing='@json($stockTransfer->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "notes" => $item->notes]))'></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A transfer needs at least one item and different source/destination warehouses.
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

    <select class="d-none stock-transfer-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
