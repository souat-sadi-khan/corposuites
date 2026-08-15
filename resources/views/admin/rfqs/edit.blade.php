<form class="ajax-form rfq-form" method="POST" action="{{ route('admin.rfqs.update', $rfq->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit RFQ</h5>
            <p>Update RFQ: {{ $rfq->rfq_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Source Purchase Request</label>
                <select name="purchase_request_id" class="form-select select">
                    <option value="">No Source Request</option>
                    @foreach($purchaseRequests as $purchaseRequest)
                        <option value="{{ $purchaseRequest->id }}" {{ old('purchase_request_id', $rfq->purchase_request_id) == $purchaseRequest->id ? 'selected' : '' }}>{{ $purchaseRequest->request_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>RFQ Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="rfq_date" value="{{ old('rfq_date', optional($rfq->rfq_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date" value="{{ old('due_date', optional($rfq->due_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>RFQ Status</label>
                <select name="rfq_status" class="form-select">
                    @foreach(\App\Models\Rfq::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('rfq_status', $rfq->rfq_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $rfq->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $rfq->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $rfq->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Requested Products <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm rfq-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="rfq-item-rows" data-existing='@json($rfq->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "notes" => $item->notes]))'></div>

        <hr>

        <label class="mb-2 d-block">Send To Vendors <span class="req">*</span></label>
        <div class="fm-grid">
            @php($selectedVendorIds = $rfq->rfqVendors->pluck('vendor_id')->toArray())
            @foreach($vendors as $vendor)
                <div class="fm-field" style="max-width:250px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="vendor_ids[]" value="{{ $vendor->id }}" id="rfqVendor{{ $vendor->id }}" {{ in_array($vendor->id, $selectedVendorIds) ? 'checked' : '' }}>
                        <label class="form-check-label" for="rfqVendor{{ $vendor->id }}">
                            {{ $vendor->name }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. An RFQ needs at least one item and one vendor.
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

    <select class="d-none rfq-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
