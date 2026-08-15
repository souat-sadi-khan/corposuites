<form class="ajax-form purchase-request-form" method="POST" action="{{ route('admin.purchase-requests.update', $purchaseRequest->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Purchase Request</h5>
            <p>Update request: {{ $purchaseRequest->request_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Requested By</label>
                <select name="requested_by" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ old('requested_by', $purchaseRequest->requested_by) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Department</label>
                <select name="department_id" class="form-select select">
                    <option value="">No Department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id', $purchaseRequest->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Required Date</label>
                <input type="date" class="form-control" name="required_date" value="{{ old('required_date', optional($purchaseRequest->required_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Reason</label>
                <input type="text" class="form-control" name="reason" value="{{ old('reason', $purchaseRequest->reason) }}">
            </div>
            <div class="fm-field">
                <label>Request Status</label>
                <select name="request_status" class="form-select">
                    @foreach(\App\Models\PurchaseRequest::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('request_status', $purchaseRequest->request_status) == $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $purchaseRequest->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $purchaseRequest->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $purchaseRequest->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Requested Items <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm purchase-request-item-add">
                <i class="ri-add-line"></i> Add Item
            </button>
        </div>
        <div class="purchase-request-item-rows" data-existing='@json($purchaseRequest->items->map(fn($item) => ["product_id" => $item->product_id, "quantity" => $item->quantity, "notes" => $item->notes]))'></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. A request needs at least one item.
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

    <select class="d-none purchase-request-product-options">
        <option value="">Select Product</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
        @endforeach
    </select>
</form>
