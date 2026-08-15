<form class="ajax-form" method="POST" action="{{ route('admin.asset-purchases.update', $assetPurchase->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Asset Purchase</h5>
            <p>Update: {{ $assetPurchase->asset->asset_code ?? 'Asset removed' }} &middot; Total {{ number_format($assetPurchase->total_cost, 2) }}</p>
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
                        <option value="{{ $asset->id }}" {{ (int) $assetPurchase->asset_id === $asset->id ? 'selected' : '' }}>{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Vendor</label>
                <select name="vendor_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ (int) $assetPurchase->vendor_id === $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Purchase Order</label>
                <select name="purchase_order_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}" {{ (int) $assetPurchase->purchase_order_id === $purchaseOrder->id ? 'selected' : '' }}>{{ $purchaseOrder->po_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Invoice Number</label>
                <input type="text" class="form-control" name="invoice_number" value="{{ old('invoice_number', $assetPurchase->invoice_number) }}">
            </div>
            <div class="fm-field">
                <label>Purchase Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', $assetPurchase->purchase_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Purchase Cost <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="purchase_cost" value="{{ old('purchase_cost', $assetPurchase->purchase_cost) }}" required>
            </div>
            <div class="fm-field">
                <label>Additional Cost</label>
                <input type="number" step="0.01" min="0" class="form-control" name="additional_cost" value="{{ old('additional_cost', $assetPurchase->additional_cost) }}">
            </div>
            <div class="fm-field">
                <label>Warranty Expiry</label>
                <input type="date" class="form-control" name="warranty_expiry" value="{{ old('warranty_expiry', $assetPurchase->warranty_expiry?->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $assetPurchase->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $assetPurchase->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $assetPurchase->notes) }}</textarea>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
