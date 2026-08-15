<form class="ajax-form" method="POST" action="{{ route('admin.asset-location-movements.update', $assetLocationMovement->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Asset Movement</h5>
            <p>{{ $assetLocationMovement->asset->asset_code ?? 'Asset removed' }} &middot; {{ $assetLocationMovement->is_current ? 'Current location' : 'Historical record' }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Asset <span class="req">*</span></label>
                <select name="asset_id" class="form-select select" required>
                    <option value="">Select an asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ (int) $assetLocationMovement->asset_id === $asset->id ? 'selected' : '' }}>{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Moved To <span class="req">*</span></label>
                <select name="asset_location_id" class="form-select select" required>
                    <option value="">Select a location</option>
                    @foreach($assetLocations as $location)
                        <option value="{{ $location->id }}" {{ (int) $assetLocationMovement->asset_location_id === $location->id ? 'selected' : '' }}>{{ $location->name }} ({{ $location->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Moved On <span class="req">*</span></label>
                <input type="date" class="form-control" name="moved_date" value="{{ old('moved_date', $assetLocationMovement->moved_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $assetLocationMovement->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $assetLocationMovement->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Reason</label>
                <textarea class="form-control" name="reason" rows="2">{{ old('reason', $assetLocationMovement->reason) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $assetLocationMovement->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Changing the date can change which movement counts as the asset's current location.
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
