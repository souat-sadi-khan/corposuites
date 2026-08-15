<form class="ajax-form stock-entry-form" method="POST" action="{{ route('admin.stock-entries.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Stock Entry</h5>
            <p>Record products received into a warehouse</p>
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
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Warehouse Location</label>
                <select name="warehouse_location_id" class="form-select select">
                    <option value="">No Specific Location</option>
                    @foreach($warehouseLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }} ({{ $location->warehouse->name ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Source Goods Receipt</label>
                <select name="goods_receipt_id" class="form-select select">
                    <option value="">No Source Receipt</option>
                    @foreach($goodsReceipts as $goodsReceipt)
                        <option value="{{ $goodsReceipt->id }}">{{ $goodsReceipt->receipt_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Entry Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="entry_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" placeholder="e.g., Purchase Receipt, Production Output">
            </div>
            <div class="fm-field">
                <label>Entry Status</label>
                <select name="entry_status" class="form-select">
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes for this stock entry"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Received Products <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm stock-entry-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="stock-entry-item-rows"></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A stock entry needs at least one item.
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

    {{-- Product options source, consumed client-side by stock-entries.js. No per-row AJAX. --}}
    <select class="d-none stock-entry-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
