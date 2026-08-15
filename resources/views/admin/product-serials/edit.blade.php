<form class="ajax-form product-serial-form" method="POST" action="{{ route('admin.product-serials.update', $productSerial->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Product Serial</h5>
            <p>Update serial: {{ $productSerial->serial_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" class="form-select select" required>
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id', $productSerial->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Serial Number <span class="req">*</span></label>
                <input type="text" class="form-control" name="serial_number" value="{{ old('serial_number', $productSerial->serial_number) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Warehouse</label>
                <select name="warehouse_id" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $productSerial->warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Serial Status</label>
                <select name="serial_status" class="form-select">
                    @foreach(\App\Models\ProductSerial::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('serial_status', $productSerial->serial_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $productSerial->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $productSerial->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $productSerial->notes) }}</textarea>
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
