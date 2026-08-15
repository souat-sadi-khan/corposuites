<form class="ajax-form" method="POST" action="{{ route('admin.designations.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Designation</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Department</label>
                <select name="department_id" class="form-select select">
                    <option value="">No Department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" required autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note"><i class="ri-information-line"></i> Fields marked with * are required</span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal"><i class="ri-close-large-line me-1"></i> Cancel</button>
            <button type="submit" class="btn-nx-primary" id="submit"><i class="ri-check-line me-1"></i> Create</button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Submitting...
            </button>
        </div>
    </div>
</form>
