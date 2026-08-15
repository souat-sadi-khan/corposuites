<form class="ajax-form" method="POST" action="{{ route('admin.asset-disposals.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Record Disposal</h5>
            <p>Take an asset out of service — sold, scrapped, donated or written off</p>
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
                    <small class="text-muted">Every asset already has a disposal record.</small>
                @endif
            </div>
            <div class="fm-field">
                <label>Disposal Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="disposal_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Disposal Method <span class="req">*</span></label>
                <select name="disposal_method" class="form-select" required>
                    <option value="sold">Sold</option>
                    <option value="scrapped">Scrapped</option>
                    <option value="donated">Donated</option>
                    <option value="written_off">Written Off</option>
                    <option value="traded_in">Traded In</option>
                    <option value="lost">Lost</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Recipient / Buyer</label>
                <input type="text" class="form-control" name="recipient" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Proceeds</label>
                <input type="number" step="0.01" min="0" class="form-control" name="proceeds" value="0">
            </div>
            <div class="fm-field">
                <label>Disposal State <span class="req">*</span></label>
                <select name="disposal_status" class="form-select" required>
                    <option value="completed" selected>Completed</option>
                    <option value="pending">Pending</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Approved By</label>
                <select name="approved_by" class="form-select select">
                    <option value="">None</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
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
                <label>Reason</label>
                <textarea class="form-control" name="reason" rows="2" placeholder="Why the asset is being disposed of"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Book value and gain/loss are worked out automatically. A completed disposal marks the asset Disposed.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Record
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
