<form class="ajax-form" method="POST" action="{{ route('admin.asset-purchases.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Record Asset Purchase</h5>
            <p>Capture how and when a registered asset was acquired</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Asset <span class="req">*</span></label>
                <select name="asset_id" class="form-select select" required>
                    <option value="">Select an asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
                @if($assets->isEmpty())
                    <small class="text-muted">Every registered asset already has purchase information recorded.</small>
                @endif
            </div>
            <div class="fm-field">
                <label>Vendor</label>
                <select name="vendor_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Purchase Order</label>
                <select name="purchase_order_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}">{{ $purchaseOrder->po_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Invoice Number</label>
                <input type="text" class="form-control" name="invoice_number" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Purchase Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="purchase_date" required>
            </div>
            <div class="fm-field">
                <label>Purchase Cost <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="purchase_cost" value="0" required>
            </div>
            <div class="fm-field">
                <label>Additional Cost</label>
                <input type="number" step="0.01" min="0" class="form-control" name="additional_cost" value="0" placeholder="Freight, installation, setup">
            </div>
            <div class="fm-field">
                <label>Warranty Expiry</label>
                <input type="date" class="form-control" name="warranty_expiry">
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
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Total capitalised cost is purchase cost plus additional cost. Each asset can have one purchase record.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Save
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
