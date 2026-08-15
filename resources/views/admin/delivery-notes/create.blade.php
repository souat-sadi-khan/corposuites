<form class="ajax-form" method="POST" action="{{ route('admin.delivery-notes.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Delivery Note</h5>
            <p>Issue a delivery note document for a shipped delivery</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Delivery <span class="req">*</span></label>
                <select name="delivery_id" class="form-select select" required>
                    <option value="">Select Delivery</option>
                    @foreach($deliveries as $delivery)
                        <option value="{{ $delivery->id }}">{{ $delivery->delivery_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Issued Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="issued_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Received By</label>
                <input type="text" class="form-control" name="received_by" placeholder="Name of recipient">
            </div>
            <div class="fm-field">
                <label>Received Date</label>
                <input type="date" class="form-control" name="received_date">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Remarks</label>
                <textarea class="form-control" name="remarks" rows="2" placeholder="Notes about this delivery note"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. Only deliveries without an existing note are listed.
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
</form>
