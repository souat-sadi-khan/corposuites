<form class="ajax-form" method="POST" action="{{ route('admin.asset-locations.update', $assetLocation->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Asset Location</h5>
            <p>Update: {{ $assetLocation->name }} ({{ $assetLocation->code }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Location Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $assetLocation->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Location Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" value="{{ old('code', $assetLocation->code) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Location Type <span class="req">*</span></label>
                <select name="location_type" class="form-select" required>
                    @foreach(['office' => 'Office', 'branch' => 'Branch', 'warehouse' => 'Warehouse', 'site' => 'Site', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" {{ $assetLocation->location_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Department</label>
                <select name="department_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ (int) $assetLocation->department_id === $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Building</label>
                <input type="text" class="form-control" name="building" value="{{ old('building', $assetLocation->building) }}">
            </div>
            <div class="fm-field">
                <label>Floor</label>
                <input type="text" class="form-control" name="floor" value="{{ old('floor', $assetLocation->floor) }}">
            </div>
            <div class="fm-field">
                <label>Room</label>
                <input type="text" class="form-control" name="room" value="{{ old('room', $assetLocation->room) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $assetLocation->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $assetLocation->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2">{{ old('address', $assetLocation->address) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $assetLocation->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Deleting a location also removes its movement history.
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
