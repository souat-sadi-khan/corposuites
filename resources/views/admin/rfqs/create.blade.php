<form class="ajax-form rfq-form" method="POST" action="{{ route('admin.rfqs.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add RFQ</h5>
            <p>Request quotations from vendors</p>
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
                        <option value="{{ $purchaseRequest->id }}">{{ $purchaseRequest->request_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>RFQ Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="rfq_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date">
            </div>
            <div class="fm-field">
                <label>RFQ Status</label>
                <select name="rfq_status" class="form-select">
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="closed">Closed</option>
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
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes for this RFQ"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Requested Products <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm rfq-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="rfq-item-rows"></div>

        <hr>

        <label class="mb-2 d-block">Send To Vendors <span class="req">*</span></label>
        <div class="fm-grid">
            @foreach($vendors as $vendor)
                <div class="fm-field" style="max-width:250px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="vendor_ids[]" value="{{ $vendor->id }}" id="rfqVendor{{ $vendor->id }}">
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
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>

    {{-- Product options source, consumed client-side by rfqs.js. No per-row AJAX. --}}
    <select class="d-none rfq-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
