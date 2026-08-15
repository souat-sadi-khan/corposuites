<form class="ajax-form" method="POST" action="{{ route('admin.asset-locations.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Asset Location</h5>
            <p>A place assets can physically sit — office, branch, site or store</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Location Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Head Office, Karachi Branch" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Location Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" placeholder="e.g., HO, KHI-01" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Location Type <span class="req">*</span></label>
                <select name="location_type" class="form-select" required>
                    <option value="office">Office</option>
                    <option value="branch">Branch</option>
                    <option value="warehouse">Warehouse</option>
                    <option value="site">Site</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Department</label>
                <select name="department_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Building</label>
                <input type="text" class="form-control" name="building" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Floor</label>
                <input type="text" class="form-control" name="floor" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Room</label>
                <input type="text" class="form-control" name="room" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Department links to the existing HRM departments list. Record where assets actually are under Movement Tracking.
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
